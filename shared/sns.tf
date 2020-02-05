resource "aws_sns_topic" "alerts" {
  name = "alerts"
}

module "notify_slack" {
  source  = "terraform-aws-modules/notify-slack/aws"
  version = "~> 2.0"

  sns_topic_name   = aws_sns_topic.alerts.name
  create_sns_topic = false

  lambda_function_name = "notify-slack"

  slack_webhook_url = data.aws_secretsmanager_secret_version.slack_webhook_url.secret_string
  slack_channel     = local.account.name == "production" ? "#opg-digideps-team" : "#opg-digideps-devs"
  slack_username    = "aws"
  slack_emoji       = ":warning:"

  tags = local.default_tags
}

resource "aws_sns_topic" "availability-alert" {
  provider     = aws.us-east-1
  name         = "availability-alert"
  display_name = "${local.default_tags["application"]} ${local.default_tags["environment-name"]} Availability Alert"
}

module "notify_slack_us-east-1" {
  source  = "terraform-aws-modules/notify-slack/aws"
  version = "~> 2.0"

  providers = {
    aws = aws.us-east-1
  }

  sns_topic_name   = aws_sns_topic.availability-alert.name
  create_sns_topic = false
  create           = local.account.name != "development"

  lambda_function_name = "notify-slack"

  slack_webhook_url = data.aws_secretsmanager_secret_version.slack_webhook_url.secret_string
  slack_channel     = local.account.name == "production" ? "#opg-digideps-team" : "#opg-digideps-devs"
  slack_username    = "aws"
  slack_emoji       = ":warning:"

  tags = local.default_tags
}
