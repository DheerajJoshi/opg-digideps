resource "aws_ecs_task_definition" "front" {
  family                   = "front-${local.environment}"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.front_container}]"
  task_role_arn            = aws_iam_role.front.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

resource "aws_ecs_service" "front" {
  name                    = aws_ecs_task_definition.front.family
  cluster                 = aws_ecs_cluster.main.id
  task_definition         = aws_ecs_task_definition.front.arn
  desired_count           = local.account.task_count
  launch_type             = "FARGATE"
  enable_ecs_managed_tags = true
  propagate_tags          = "SERVICE"
  tags                    = local.default_tags

  network_configuration {
    security_groups  = [module.front_service_security_group.id]
    subnets          = data.aws_subnet.private.*.id
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.front.arn
    container_name   = "front_app"
    container_port   = 443
  }

  depends_on = [aws_lb_listener.front_https]
}

locals {
  front_container = <<EOF
  {
    "cpu": 0,
    "essential": true,
    "image": "${local.images.client}",
    "mountPoints": [],
    "name": "front_app",
    "portMappings": [{
      "containerPort": 443,
      "hostPort": 443,
      "protocol": "tcp"
    }],
    "volumesFrom": [],
    "logConfiguration": {
      "logDriver": "awslogs",
      "options": {
        "awslogs-group": "${aws_cloudwatch_log_group.opg_digi_deps.name}",
        "awslogs-region": "eu-west-1",
        "awslogs-stream-prefix": "${aws_iam_role.front.name}"
      }
    },
    "secrets": [
      { "name": "API_CLIENT_SECRET", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.front_api_client_secret.name}" },
      { "name": "SECRET", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.front_frontend_secret.name}" }
    ],
    "environment": [
      { "name": "ADMIN_HOST", "value": "https://${aws_route53_record.admin.fqdn}" },
      { "name": "API_URL", "value": "https://${local.api_service_fqdn}" },
      { "name": "BEHAT_CONTROLLER_ENABLED", "value": "${local.account.test_enabled ? "true" : "false"}" },
      { "name": "EMAIL_DOMAIN", "value": "${local.domain}" },
      { "name": "EMAIL_SEND_INTERNAL", "value": "${local.account.is_production == 1 ? "true" : "false"}" },
      { "name": "FILESCANNER_SSLVERIFY", "value": "False" },
      { "name": "FILESCANNER_URL", "value": "http://${local.scan_service_fqdn}:8080" },
      { "name": "GA_DEFAULT", "value": "${local.account.ga_default}" },
      { "name": "GA_GDS", "value": "${local.account.ga_gds}" },
      { "name": "NONADMIN_HOST", "value": "https://${aws_route53_record.front.fqdn}" },
      { "name": "ROLE", "value": "front" },
      { "name": "S3_BUCKETNAME", "value": "pa-uploads-${local.environment}" },
      { "name": "SESSION_REDIS_DSN", "value": "redis://${aws_route53_record.front_redis.fqdn}" },
      { "name": "SMTP_DEFAULT_PASSWORD", "value": "${aws_iam_access_key.ses.ses_smtp_password}" },
      { "name": "SMTP_DEFAULT_USER", "value": "${aws_iam_access_key.ses.id}" },
      { "name": "TEST_ENABLED", "value": "${local.account.test_enabled}" },
      { "name": "OPG_DOCKER_TAG", "value": "${var.OPG_DOCKER_TAG}" },
      { "name": "WKHTMLTOPDF_ADDRESS", "value": "http://${local.wkhtmltopdf_service_fqdn}" }
    ]
  }

EOF

}
