package codacy.metrics

import codacy.docker.api.metrics.LineComplexity
import com.codacy.docker.api.utils.{CommandResult, CommandRunner}

import scala.util.Try
import scala.xml.{NodeSeq, XML}

final case class PHPMethod(filename: String, line: Option[Int], complexity: Option[Int])

final case class PHPClass(filename: String, methods: Seq[PHPMethod])

final case class PHPFile(name: String,
                         classes: Seq[PHPClass],
                         functions: Seq[PHPMethod],
                         loc: Option[Int],
                         cloc: Option[Int]) {
  private val allMethods: Seq[PHPMethod] = classes.flatMap(_.methods) ++ functions
  val nrOfMethods: Int = allMethods.length
  val nrOfClasses: Int = classes.length

  val complexity: Option[Int] =
    Option(allMethods.flatMap(_.complexity)).filter(_.nonEmpty).map(_.max)

  val lineComplexities: Seq[LineComplexity] = for {
    method <- allMethods
    line <- method.line
    cpx <- method.complexity
  } yield LineComplexity(line, cpx)
}

object Tool {

  def runMetrics(directory: String): Try[Seq[PHPFile]] = {
    for {
      fileOutputStr <- runTool(directory)
      xml <- Try(XML.loadString(fileOutputStr))
      metrics <- parseMetrics(xml)
        .toRight(new Exception("Could not parse XML returned from tool"))
        .toTry
    } yield metrics
  }

  private def baseCommand(outfile: String, dir: String): Either[Throwable, CommandResult] = {
    CommandRunner.exec(List("php", "pdepend.phar", s"--summary-xml=$outfile", dir))
  }

  private def runTool(directoryPath: String): Try[String] =
    better.files.File
      .temporaryFile()
      .apply { outputFile =>
        baseCommand(outputFile.toJava.getAbsolutePath, directoryPath).toTry
          .map(_ => outputFile.contentAsString)
      }

  private def parseLoc(node: NodeSeq): Option[Int] =
    Try((node \@ "eloc").toInt).toOption

  private def parseCloc(node: NodeSeq): Option[Int] =
    Try((node \@ "cloc").toInt).toOption

  private def parseCyclomaticComplexity(node: NodeSeq): Option[Int] =
    Try((node \@ "ccn").toInt).toOption

  private def parseName(node: NodeSeq): Option[String] =
    Try(node \@ "name").toOption

  private def parseLine(node: NodeSeq): Option[Int] =
    Try((node \@ "start").toInt).toOption

  private def parseClass(node: NodeSeq): Option[PHPClass] =
    parseName(node \ "file").map { filename =>
      PHPClass(filename = filename, methods = (node \\ "method").map(parseMethod(_, filename)))
    }

  private def parseMethod(node: NodeSeq, filename: String): PHPMethod =
    PHPMethod(filename = filename, line = parseLine(node), complexity = parseCyclomaticComplexity(node))

  private def parseMetrics(node: NodeSeq): Option[Seq[PHPFile]] = {
    val files = node \\ "files" \\ "file"

    (node \\ "package")
      .map { packageNode =>
        val classes = (packageNode \\ "class").flatMap { classNode =>
          parseClass(classNode)
        }

        val functions = (packageNode \\ "function").flatMap { functionNode =>
          parseName(functionNode \ "file").map { filename =>
            parseMethod(functionNode, filename)
          }
        }

        (classes, functions)
      }
      .reduceOption[(Seq[PHPClass], Seq[PHPMethod])] {
        case ((accumClasses, accumFunctions), (classes, functions)) =>
          (accumClasses ++ classes, accumFunctions ++ functions)
      }
      .map {
        case (allClasses, allFunctions) =>
          files.flatMap { fileNode =>
            parseName(fileNode).map { fileName =>
              val fileClasses = allClasses.filter(_.filename == fileName)
              val fileFunctions = allFunctions.filter(_.filename == fileName)

              PHPFile(name = fileName,
                      classes = fileClasses,
                      functions = fileFunctions,
                      loc = parseLoc(fileNode),
                      cloc = parseCloc(fileNode))
            }
          }
      }

  }

}
