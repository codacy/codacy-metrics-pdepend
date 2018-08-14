# codacy-metrics-pdepend

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/340bcc650709447c9fc394e00ae81b67)](https://www.codacy.com/app/Codacy/codacy-metrics-pdepend?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=codacy/codacy-metrics-pdepend&amp;utm_campaign=Badge_Grade)
[![CircleCI](https://circleci.com/gh/codacy/codacy-metrics-pdepend.svg?style=svg)](https://circleci.com/gh/codacy/codacy-metrics-pdepend)
[![Docker Version](https://images.microbadger.com/badges/version/codacy/codacy-metrics-pdepend.svg)](https://microbadger.com/images/codacy/codacy-metrics-pdepend "Get your own version badge on microbadger.com")

This is the docker engine we use at Codacy to have support for PHP metrics through [PDepend](https://pdepend.org/).

## Usage

You can create the docker by doing:

```bash
./scripts/publish.sh
```

The docker is ran with the following command:

```bash
docker run -it -v $srcDir:/src  <DOCKER_NAME>:<DOCKER_VERSION>
docker run -it -v $PWD/src/test/resources:/src codacy/codacy-metrics-pdepend:latest
```

## Test

Before running the tests, you need to install PDepend. The tool [website](https://pdepend.org/) provides you instructions on how to do so.

After that, you can run the tests:

```bash
./scripts/test.sh
```

## What is Codacy

[Codacy](https://www.codacy.com/) is an Automated Code Review Tool that monitors your technical debt, helps you improve your code quality, teaches best practices to your developers, and helps you save time in Code Reviews.

### Among Codacy’s features

- Identify new Static Analysis issues
- Commit and Pull Request Analysis with GitHub, BitBucket/Stash, GitLab (and also direct git repositories)
- Auto-comments on Commits and Pull Requests
- Integrations with Slack, HipChat, Jira, YouTrack
- Track issues in Code Style, Security, Error Proneness, Performance, Unused Code and other categories

Codacy also helps keep track of Code Coverage, Code Duplication, and Code Complexity.

Codacy supports PHP, Python, Ruby, Java, JavaScript, and Scala, among others.

### Free for Open Source

Codacy is free for Open Source projects.