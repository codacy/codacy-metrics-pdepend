import sbt._

object Dependencies {

  object Codacy {
    val metricsSeed = "com.codacy" %% "codacy-metrics-scala-seed" % "0.1.219"
  }

  private val specs2Version = "4.3.2"
  val specs2 = "org.specs2" %% "specs2-core" % specs2Version
}
