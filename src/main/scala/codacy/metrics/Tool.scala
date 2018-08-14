package codacy.metrics

import codacy.docker.api.metrics.LineComplexity
import com.codacy.docker.api.utils.{CommandResult, CommandRunner}

import scala.util.Try
import scala.xml.{NodeSeq, XML}

final case class PHPMethod(name: String,
                           filename: String,
                           line: Option[Int],
                           complexity: Option[Int],
                           loc: Option[Int],
                           cloc: Option[Int])

final case class PHPClass(name: String, filename: String, methods: Seq[PHPMethod], loc: Option[Int], cloc: Option[Int])

final case class PHPFile(name: String,
                         classes: Seq[PHPClass],
                         functions: Seq[PHPMethod],
                         loc: Option[Int],
                         cloc: Option[Int]) {
  lazy val allMethods: Seq[PHPMethod] = classes.flatMap(_.methods) ++ functions
  lazy val nrOfMethods: Int = allMethods.length
  lazy val nrOfClasses: Int = classes.length
  lazy val complexity: Option[Int] =
    Option(allMethods.flatMap(_.complexity)).filter(_.nonEmpty).map(_.max)
  lazy val lineComplexities: Seq[LineComplexity] = for {
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
      metrics <- parseMetrics(xml).toRight(new Exception("Could not parse XML returned from tool")).toTry
    } yield metrics
  }

  private def baseCommand(outfile: String, dir: String): Either[Throwable, CommandResult] = {
    CommandRunner.exec(List("php", "pdepend.phar", s"--summary-xml=$outfile", dir))
  }

  private def runTool(directoryPath: String): Try[String] =
    Try {
      better.files.File
        .temporaryFile()
        .map { outputFile =>
          baseCommand(outputFile.toJava.getAbsolutePath, directoryPath).toTry
            .map(_ => outputFile.contentAsString)
        }
        .get
    }.flatten

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

  private def parseClass(node: NodeSeq, packageName: Option[String]): Option[PHPClass] =
    parseName(node \ "file").flatMap { filename =>
      parseName(node).map { name =>
        val packagePrefix = packageName
          .map { p =>
            s"$p."
          }
          .getOrElse("")
        val className = s"$packagePrefix$name"

        PHPClass(name = className,
                 filename = filename,
                 methods = (node \\ "method").flatMap(parseMethod(_, filename, Some(className))),
                 loc = parseLoc(node),
                 cloc = parseCloc(node))
      }
    }

  private def parseMethod(node: NodeSeq, filename: String, parentName: Option[String]): Option[PHPMethod] =
    parseName(node).map { name =>
      val classPrefix = parentName
        .map { c =>
          s"$c."
        }
        .getOrElse("")

      PHPMethod(name = s"$classPrefix$name",
                filename = filename,
                line = parseLine(node),
                complexity = parseCyclomaticComplexity(node),
                loc = parseLoc(node),
                cloc = parseCloc(node))
    }

  private def parseFile(node: NodeSeq): Option[PHPFile] = parseName(node).map { name =>
    PHPFile(name = name, classes = Seq.empty, functions = Seq.empty, loc = parseLoc(node), cloc = parseCloc(node))
  }

  private def parsePackage(node: NodeSeq): Option[String] =
    parseName(node).filter(_ != "+global")

  private def parseMetrics(node: NodeSeq): Option[Seq[PHPFile]] = {
    val files = (node \\ "files" \\ "file").flatMap(parseFile)

    (node \\ "package")
      .map { packageNode =>
        val packageName = parsePackage(packageNode)

        val classes = (packageNode \\ "class").flatMap { classNode =>
          parseClass(classNode, packageName)
        }

        val functions = (packageNode \\ "function").flatMap { functionNode =>
          parseName(functionNode \ "file").flatMap { filename =>
            parseMethod(functionNode, filename, packageName)
          }
        }

        (classes, functions)
      }
      .reduceOption[(Seq[PHPClass], Seq[PHPMethod])] {
        case ((accumClasses, accumFunctions), (classes, functions)) =>
          (accumClasses ++ classes, accumFunctions ++ functions)
      }
      .map {
        case (classes, functions) =>
          files.map { file =>
            val fileClasses = classes.filter(_.filename == file.name)
            val fileFunctions = functions.filter(_.filename == file.name)
            file.copy(classes = fileClasses, functions = fileFunctions)
          }
      }

  }

}
