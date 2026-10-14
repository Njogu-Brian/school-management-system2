#!/usr/bin/env bash
# Shared helpers. Passwords are never loaded into the shell.

dr_env() {
  local php_bin="${PHP_BIN:-php}"
  "$php_bin" "$DR_PHP" env-value --env "$1" --key "$2"
}

dr_write_cnf() {
  local dest="$1"
  local env_file="$2"
  local php_bin="${PHP_BIN:-php}"
  "$php_bin" "$DR_PHP" write-defaults --env "$env_file" --out "$dest"
  chmod 600 "$dest" 2>/dev/null || true
}

dr_mysql() {
  mysql --defaults-extra-file="$1" --batch --skip-column-names "${@:2}"
}
