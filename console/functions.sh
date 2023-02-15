#!/usr/bin/env bash

function setQueueThreads() {
    QUEUE_THREADS="${1}"
    CONF_FILE="${2}"

    if [[ "${QUEUE_THREADS}" =~ ^[+-]?[0-9]+$ ]]; then
        sed -i -re "s|numprocs=[0-9]{1,3}|numprocs=$QUEUE_THREADS|gm" "/etc/supervisor/conf.d/$CONF_FILE"
        echo "set to config."
    else
        echo "wrong QUEUE_THREADS value"
    fi
}
