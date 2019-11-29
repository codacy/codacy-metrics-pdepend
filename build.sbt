import com.typesafe.sbt.packager.docker.{Cmd, DockerAlias}

enablePlugins(JavaAppPackaging)
enablePlugins(DockerPlugin)
organization := "com.codacy"
scalaVersion := "2.13.1"
name := "codacy-metrics-pdepend"
// App Dependencies
libraryDependencies ++= Seq("com.codacy" %% "codacy-metrics-scala-seed" % "0.2.0",
                            "org.scala-lang.modules" %% "scala-xml" % "1.2.0",
                            "org.specs2" %% "specs2-core" % "4.8.0" % Test)

mappings in Universal ++= {
  (resourceDirectory in Compile).map { resourceDir: File =>
    val src = resourceDir / "docs"
    val dest = "/docs"

    for {
      path <- src.allPaths.get if !path.isDirectory
    } yield path -> path.toString.replaceFirst(src.toString, dest)
  }
}.value ++ Seq(

)

Docker / packageName := packageName.value
dockerBaseImage := "openjdk:8-jre-alpine"
Docker / daemonUser := "docker"
Docker / daemonGroup := "docker"
dockerEntrypoint := Seq(s"/opt/docker/bin/${name.value}")

val pDependVersion = scala.io.Source.fromFile(".pdepend-version").mkString.trim
dockerCommands := dockerCommands.value.flatMap {
  case cmd @ Cmd("ADD", _) =>
    Seq(Cmd("RUN", "adduser -u 2004 -D docker"),
        cmd,
        Cmd("RUN", s"""apk update &&
                      |apk --no-cache add bash curl php php-tokenizer php-xml php-cli php-pdo
                      |               php-curl php-json php-iconv php-phar php-ctype php-openssl php-dom""".stripMargin.replaceAll(System.lineSeparator(), " ")),
        Cmd("RUN", s"""apk --no-cache add composer &&
                      |composer global require pdepend/pdepend &&
                      |rm -rf /tmp/*""".stripMargin.replaceAll(System.lineSeparator(), " ")),
        Cmd("RUN", "mv /opt/docker/docs /docs"))

  case other => Seq(other)
}
