package codacy.metrics

import com.codacy.docker.api.metrics.DockerMetrics

object Metrics extends DockerMetrics(PDepend)()
