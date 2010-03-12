#!/bin/bash

#     ./scripts/accounting_load.sh
# 
#     vpsAdmin
#     Web-admin interface for OpenVZ (see http://openvz.org)
#     Copyright (C) 2008-2009 Pavel Snajdr, snajpa@snajpa.net
# 
#     This program is free software: you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation, either version 3 of the License, or
#     (at your option) any later version.
# 
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
# 
#     You should have received a copy of the GNU General Public License
#     along with this program.  If not, see <http://www.gnu.org/licenses/>.


iptables -N anix
iptables -N atranzit
iptables -N aztotal
iptables -N znix
ip6tables -N aztotal
iptables -Z anix
iptables -Z atranzit
iptables -Z aztotal
iptables -Z znix
ip6tables -Z aztotal

for subnet in `cat $1`;do
	iptables -A znix -s $subnet -g anix
	iptables -A znix -d $subnet -g anix
done
iptables -A znix -g atranzit

iptables -A FORWARD -j znix
iptables -A FORWARD -j aztotal
ip6tables -A FORWARD -j aztotal
