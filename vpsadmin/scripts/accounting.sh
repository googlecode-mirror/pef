#!/bin/sh
TMP_DIR=/tmp
TMP_FILE=all_bytes_iptables
TMP_SQL=all_sql_file_iptables

IPTABLES=iptables
IPTABLES_OPTIONS="-L ${1} -n -x -v "
GREP=grep
SED=sed
TR=tr
CAT=cat
CUT=cut

cd ${TMP_DIR}
${IPTABLES} ${IPTABLES_OPTIONS} | ${TR} -s ' ' | ${SED} -e 's/^ //' \
| ${GREP} "^[0-9]" | ${CUT} -d' ' -f2,8,9 > ${TMP_FILE}

#
# Bytes for output
#
echo "Start for OUTPUT"
${CAT} ${TMP_FILE} | ${CUT} -d' ' -f1,2 | ${GREP} -v "0.0.0.0/0"

#
# Bytes for input
#
echo "Start for INPUT"
${CAT} ${TMP_FILE} | ${CUT} -d' ' -f1,3 | ${GREP} -v "0.0.0.0/0" 