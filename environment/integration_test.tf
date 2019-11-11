module "integration_test" {
  source = "./task"
  name   = "integration-test"

  cluster_name          = aws_ecs_cluster.main.name
  container_definitions = "[${local.integration_test_container}]"
  default_tags          = local.default_tags
  environment           = local.environment
  execution_role_arn    = aws_iam_role.execution_role.arn
  subnet_ids            = data.aws_subnet.private[*].id
  task_role_arn         = data.aws_iam_role.sync.arn
  vpc_id                = data.aws_vpc.vpc.id
}

resource "aws_security_group_rule" "integration_test_postgres_out_rds" {
  from_port                = 5432
  protocol                 = "tcp"
  security_group_id        = module.integration_test.security_group_id
  to_port                  = 5432
  type                     = "egress"
  source_security_group_id = module.api_rds_security_group.id
}

resource "aws_security_group_rule" "integration_test_https_out" {
  from_port         = 443
  protocol          = "tcp"
  security_group_id = module.integration_test.security_group_id
  to_port           = 443
  type              = "egress"
  cidr_blocks       = ["0.0.0.0/0"]
}

locals {
  integration_test_container = <<EOF
  {
    "name": "integration-test",
    "image": "${local.images.test}",
    "logConfiguration": {
      "logDriver": "awslogs",
      "options": {
        "awslogs-group": "${aws_cloudwatch_log_group.opg_digi_deps.name}",
        "awslogs-region": "eu-west-1",
        "awslogs-stream-prefix": "${aws_iam_role.test.name}"
      }
    },
    "secrets": [
      { "name": "PGPASSWORD", "valueFrom": "${data.aws_secretsmanager_secret.database_password.arn}" },
      { "name": "SECRET", "valueFrom": "${data.aws_secretsmanager_secret.front_frontend_secret.arn}" }
    ],
    "environment": [
      { "name": "PGHOST", "value": "${aws_db_instance.api.address}" },
      { "name": "PGDATABASE", "value": "${aws_db_instance.api.name}" },
      { "name": "PGUSER", "value": "digidepsmaster" },
      { "name": "ADMIN_HOST", "value": "https://${aws_route53_record.admin.fqdn}" },
      { "name": "NONADMIN_HOST", "value": "https://${aws_route53_record.front.fqdn}" }
    ]
  }
EOF
}
