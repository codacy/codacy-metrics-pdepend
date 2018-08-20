package codacy.metrics

import better.files.File
import codacy.docker.api.{MetricsConfiguration, Source}
import codacy.docker.api.metrics.{FileMetrics, LineComplexity, MetricsTool}
import com.codacy.api.dtos.{Language, Languages}
import com.codacy.docker.api.utils.{CommandResult, CommandRunner}

import scala.util.{Failure, Try}
import scala.xml.{Node, NodeSeq}

final case class PHPMethod(filename: String, line: Option[Int], complexity: Option[Int])

final case class PHPClass(filename: String, methods: Seq[PHPMethod])

object PDepend extends MetricsTool {

  override def apply(source: Source.Directory,
                     languageOpt: Option[Language],
                     files: Option[Set[Source.File]],
                     options: Map[MetricsConfiguration.Key, MetricsConfiguration.Value]): Try[List[FileMetrics]] = {

    languageOpt match {
      case Some(language) if language != Languages.PHP =>
        Failure(new Exception(s"Tried to run PDepend metrics with language: $language. Only PHP supported."))
      case _ =>
        val filesToAnalyse: Set[File] = files match {
          case Some(filesSpecified) =>
            filesSpecified.map(srcFile => File(source.path) / srcFile.path)
          case None =>
            File(source.path).listRecursively().filter(_.isRegularFile).toSet
        }

        runMetrics(source.path, filesToAnalyse)
    }
  }

  def runMetrics(directory: String, files: Set[File]): Try[List[FileMetrics]] = {
    for {
      fileOutputStr <- run(directory)
      xml <- Try(XML.loadString(fileOutputStr))
      metrics <- parseMetrics(xml, directory, files)
        .toRight(new Exception("Could not parse XML returned from tool"))
        .toTry
    } yield metrics
  }

  private def baseCommand(outfile: String, dir: String): Either[Throwable, CommandResult] = {
    CommandRunner.exec(List("php", "pdepend.phar", s"--summary-xml=$outfile", dir))
  }

  private def run(directoryPath: String): Try[String] =
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

  private def parseMetrics(node: NodeSeq, directory: String, fileSet: Set[File]): Option[List[FileMetrics]] = {
    val files = node \\ "files" \\ "file"

    val packageNodeSeq = node \\ "package"

    val classesAndMethodsSeq: Seq[(Seq[PHPClass], Seq[PHPMethod])] = packageNodeSeq.map { packageNode =>
      val classes = (packageNode \\ "class").flatMap(parseClass(_))

      val functions = for {
        functionNode <- packageNode \\ "function"
        filename <- parseName(functionNode \ "file")
      } yield parseMethod(functionNode, filename)

      (classes, functions)
    }

    val classesAndMethods: Option[(Seq[PHPClass], Seq[PHPMethod])] =
      classesAndMethodsSeq.reduceOption[(Seq[PHPClass], Seq[PHPMethod])] {
        case ((accumClasses, accumFunctions), (classes, functions)) =>
          (accumClasses ++ classes, accumFunctions ++ functions)
      }

    val fileMetricsList: Option[List[FileMetrics]] = classesAndMethods.map {
      case (allClasses, allFunctions) =>
        val metricsList: List[FileMetrics] = (for {
          fileNode <- files
          fileName <- parseName(fileNode)
          if fileSet.exists(_.toJava.getCanonicalPath == fileName)
        } yield fileMetrics(allClasses, allFunctions, fileName, directory, fileNode))(collection.breakOut)

        metricsList
    }

    fileMetricsList
  }

  private def fileMetrics(allClasses: Seq[PHPClass],
                          allFunctions: Seq[PHPMethod],
                          fileName: String,
                          directory: String,
                          fileNode: Node): FileMetrics = {
    val fileClasses = allClasses.filter(_.filename == fileName)
    val fileFunctions = allFunctions.filter(_.filename == fileName)

    val allMethods = fileClasses.flatMap(_.methods) ++ fileFunctions

    val lineComplexities: Seq[LineComplexity] = for {
      method <- allMethods
      line <- method.line
      cpx <- method.complexity
    } yield LineComplexity(line, cpx)

    val complexity: Option[Int] =
      Option(allMethods.flatMap(_.complexity)).filter(_.nonEmpty).map(_.max)

    FileMetrics(filename = File(directory).relativize(File(fileName)).toString,
                complexity = complexity,
                nrClasses = Some(fileClasses.length),
                nrMethods = Some(allMethods.length),
                loc = parseLoc(fileNode),
                cloc = parseCloc(fileNode),
                lineComplexities = lineComplexities.to[Set])
  }
}
