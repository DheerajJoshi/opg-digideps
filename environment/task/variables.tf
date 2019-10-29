variable "vpc_id" { type = string }
variable "default_tags" { type = map(string) }
variable "environment" { type = string }
variable "execution_role_arn" { type = string }
variable "name" { type = string }
variable "container_definitions" { type = string }
variable "cluster_name" { type = string }
variable "subnet_ids" { type = list(string) }
variable "task_role_arn" { type = string }

variable "memory" {
  type    = number
  default = 512
}

variable "cpu" {
  type    = number
  default = 256
}
