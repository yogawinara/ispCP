{MINUTE} {HOUR}	*	*	*	root	umask 027; {BACKUP_ROOT_DIR}/ispcp-bk-task {DMN_NAME} {DMN_ID} &>{LOG_DIR}/bk-task-{DMN_NAME}.log
