locals {
  images = {
    api          = "${data.aws_ecr_repository.images["api"].repository_url}:${var.OPG_DOCKER_TAG}"
    client       = "${data.aws_ecr_repository.images["client"].repository_url}:${var.OPG_DOCKER_TAG}"
    file_scanner = "${data.aws_ecr_repository.images["file-scanner"].repository_url}:${var.OPG_DOCKER_TAG}"
    sync         = "${data.aws_ecr_repository.images["sync"].repository_url}:${var.OPG_DOCKER_TAG}"
    test         = "${data.aws_ecr_repository.images["test"].repository_url}:${var.OPG_DOCKER_TAG}"
    wkhtmltopdf  = "${data.aws_ecr_repository.images["wkhtmltopdf"].repository_url}:${var.OPG_DOCKER_TAG}"
  }

  repositories = [
    "api",
    "client",
    "file-scanner",
    "sync",
    "test",
    "wkhtmltopdf",
  ]
}

data "aws_ecr_repository" "images" {
  for_each = toset(local.repositories)

  name     = "digideps/${each.key}"
  provider = aws.management
}
