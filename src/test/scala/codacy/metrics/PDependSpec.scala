package codacy.metrics

import com.codacy.plugins.api.Source
import com.codacy.plugins.api.languages.Languages
import com.codacy.plugins.api.metrics.{FileMetrics, LineComplexity}
import org.specs2.mutable.Specification

class PDependSpec extends Specification {

  val srcDir: Source.Directory = Source.Directory("src/test/resources")

  val binaryTreeMetrics: FileMetrics = FileMetrics("codacy/metrics/binary_tree.php",
                                                   Some(4),
                                                   Some(28),
                                                   Some(6),
                                                   Some(2),
                                                   Some(1),
                                                   Set(LineComplexity(4, 2), LineComplexity(16, 4)))

  val mergeSortMetrics: FileMetrics = FileMetrics("codacy/metrics/merge_sort.php",
                                                  Some(5),
                                                  Some(37),
                                                  Some(0),
                                                  Some(2),
                                                  Some(0),
                                                  Set(LineComplexity(2, 2), LineComplexity(11, 5)))

  "PDepend" should {
    "get metrics" in {
      "all files in a directory" in {
        val metrics = PDepend(srcDir, Some(Languages.PHP), None, Map.empty)

        val expectedMetrics = List(binaryTreeMetrics, mergeSortMetrics)

        metrics.get must containTheSameElementsAs(expectedMetrics)
      }

      "specific files" in {
        val filesToAnalyse = Set(binaryTreeMetrics).map { fileMetrics => Source.File(fileMetrics.filename)
        }

        val metrics = PDepend(srcDir, None, Some(filesToAnalyse), Map.empty)

        val expectedMetrics = List(binaryTreeMetrics)

        metrics.get must containTheSameElementsAs(expectedMetrics)
      }
    }

    "not get metrics" in {
      "wrong language" in {
        val result = PDepend(srcDir, Some(Languages.Scala), None, Map.empty)

        result must beFailedTry
      }
    }
  }
}
