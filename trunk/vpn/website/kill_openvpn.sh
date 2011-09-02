#!/usr/bin/expect
# sudo /usr/bin/kill_openvpn.sh
set CN [lindex $argv 0]
spawn telnet 127.0.0.1 9898

send "kill $CN\r"
send "exit\r"

expect eof