@echo off
..\git-bash -c "/usr/bin/sshd -o UsePrivilegeSeparation=no -o HostKey=/sshd/ssh_host_rsa_key"
show_sshd.cmd