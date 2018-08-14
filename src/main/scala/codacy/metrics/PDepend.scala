package codacy.metrics

import better.files.File
import codacy.docker.api.{MetricsConfiguration, Source}
import codacy.docker.api.metrics.{FileMetrics, MetricsTool}
import com.codacy.api.dtos.{Language, Languages}

import scala.util.{Failure, Try}

object PDepend extends MetricsTool {

  def apply(source: Source.Directory,
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

        run(source.path, filesToAnalyse)
    }
  }

  private def run(directory: String, files: Set[File]): Try[List[FileMetrics]] = {
    val maybePhpMetrics = Tool.runMetrics(directory)

    maybePhpMetrics.map { phpMetricsSeq =>
      (for {
        fileMetrics <- phpMetricsSeq
        file <- files.find { ioFile =>
          ioFile.toJava.getCanonicalPath == fileMetrics.name
        }
      } yield {
        FileMetrics(filename = File(directory).relativize(file).toString,
                    complexity = fileMetrics.complexity,
                    nrClasses = Some(fileMetrics.nrOfClasses),
                    nrMethods = Some(fileMetrics.nrOfMethods),
                    loc = fileMetrics.loc,
                    cloc = fileMetrics.cloc,
                    lineComplexities = fileMetrics.lineComplexities.to[Set])
      }).toList
    }
  }
}
