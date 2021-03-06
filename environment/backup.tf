module "backup" {
  source = "./task"
  name   = "backup"

  cluster_name          = aws_ecs_cluster.main.name
  container_definitions = "[${local.backup_container}]"
  tags                  = local.default_tags
  environment           = local.environment
  execution_role_arn    = aws_iam_role.execution_role.arn
  subnet_ids            = data.aws_subnet.private[*].id
  task_role_arn         = data.aws_iam_role.sync.arn
  vpc_id                = data.aws_vpc.vpc.id
  security_group_id     = module.backup_security_group.id
}

locals {
  backup_sg_rules = {
    ecr  = local.common_sg_rules.ecr
    logs = local.common_sg_rules.logs
    s3   = local.common_sg_rules.s3
    rds = {
      port        = 5432
      protocol    = "tcp"
      type        = "egress"
      target_type = "security_group_id"
      target      = module.api_rds_security_group.id
    }
  }
}

module "backup_security_group" {
  source = "./security_group"
  rules  = local.backup_sg_rules
  name   = "backup"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

data "aws_canonical_user_id" "development" {
  provider = aws.development
}

data "aws_canonical_user_id" "preproduction" {
  provider = aws.preproduction
}

data "aws_canonical_user_id" "production" {
  provider = aws.production
}

data "aws_kms_alias" "backup" {
  name     = "alias/backup"
  provider = aws.management
}

locals {
  backup_container = <<EOF
{
    "name": "backup",
    "image": "${local.images.sync}",
    "command": ["./backup.sh"],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${aws_cloudwatch_log_group.opg_digi_deps.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "backup"
        }
    },
    "secrets": [{
        "name": "POSTGRES_PASSWORD",
        "valueFrom": "${data.aws_secretsmanager_secret.database_password.arn}"
    }],
    "environment": [{
            "name": "S3_BUCKET",
            "value": "${data.aws_s3_bucket.backup.bucket}"
        },
        {
            "name": "S3_OPTS",
            "value": "--sse=aws:kms --sse-kms-key-id=${data.aws_kms_alias.backup.target_key_arn} --grants=read=id=${data.aws_canonical_user_id.preproduction.id},id=${data.aws_canonical_user_id.production.id}"
        },
        {
            "name": "S3_PREFIX",
            "value": "${local.environment}"
        },
        {
            "name": "POSTGRES_DATABASE",
            "value": "${local.db.name}"
        },
        {
            "name": "POSTGRES_HOST",
            "value": "${local.db.endpoint}"
        },
        {
            "name": "POSTGRES_PORT",
            "value": "${local.db.port}"
        },
        {
            "name": "POSTGRES_USER",
            "value": "${local.db.username}"
        }
    ]
}

EOF
}
