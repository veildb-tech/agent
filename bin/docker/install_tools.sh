#!/bin/sh
set -e

get_distribution() {
    lsb_dist=""
    # Every system that we officially support has /etc/os-release
    if [ -r /etc/os-release ]; then
        lsb_dist="$(. /etc/os-release && echo "$ID")"
    fi
    # Returning an empty string here should be alright since the
    # case statements don't act unless you provide an actual value
    echo "$lsb_dist"
}

command_exists() {
    command -v "$@" > /dev/null 2>&1
}

user="$(id -un 2>/dev/null || true)"
if [ "$user" != 'root' ]; then
    if command_exists sudo; then
        sh_c='sudo -E sh -c'
    elif command_exists su; then
        sh_c='su -c'
    else
        echo
        echo "Error: this installer needs the ability to run commands as root."
        echo "We are unable to find either 'sudo' or 'su' available to make this happen."
        echo
        exit 1
    fi
fi

# perform some very rudimentary platform detection
lsb_dist=$( get_distribution )
lsb_dist="$(echo "$lsb_dist" | tr '[:upper:]' '[:lower:]')"

# Install sshpass tool
install_sshpass() {
    case "$lsb_dist" in
        ubuntu|debian|raspbian)
            $sh_c 'apt-get install sshpass'
        ;;
        centos|rhel)
            $sh_c 'yum install sshpass'
        ;;
        fedora)
            $sh_c 'dnf install sshpass'
        ;;
        *)
            echo
            echo "ERROR: Unsupported distribution '$lsb_dist'"
            echo
        ;;
    esac
}

if ! command_exists sshpass; then
    install_sshpass
fi

