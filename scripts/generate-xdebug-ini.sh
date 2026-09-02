#!/usr/bin/env bash

set -e

cat <<- EOF
[xdebug]
; Default settings
zend_extension=xdebug.so
xdebug.cli_color=1
xdebug.start_with_request=trigger
xdebug.output_dir="/project"
EOF

if [[ "$XDEBUG_ENABLE_LOG" == "yes" || "$XDEBUG_ENABLE_LOG" == "1" ]]; then
cat <<- EOF
; Xdebug log enabled
xdebug.log="xdebug.log"
EOF
else
cat <<- EOF
; Xdebug log disabled
xdebug.log=
EOF
fi

if [[ "$XDEBUG_MODES" == *"profile"* ]]; then
cat <<- EOF
; Mode "profile"
xdebug.profiler_output_name="xdebug.cachegrind.out.%c-%t"
EOF
else
cat <<- EOF
; Mode "profile" not set
EOF
fi

if [[ "$XDEBUG_MODES" == *"debug"* ]]; then
cat <<- EOF
; Mode "debug"
xdebug.client_host=${XDEBUG_REMOTE_HOST}
xdebug.client_port=${XDEBUG_REMOTE_PORT}
xdebug.idekey=${XDEBUG_IDE_KEY}
xdebug.discover_client_host=false
EOF
else
cat <<- EOF
; Mode "debug" not set
EOF
fi

if [[ "$XDEBUG_MODES" == *"trace"* ]]; then
cat <<- EOF
; Mode "trace"
xdebug.trace_output_name="xdebug.trace.out.%c-%t"
xdebug.trace_format=0
xdebug.collect_return=true
xdebug.trace_options=1
EOF
else
cat <<- EOF
; Mode "trace" not set
EOF
fi

if [[ "$XDEBUG_MODES" == "off" || -z "$XDEBUG_MODES" ]]; then
cat <<- EOF
; Mode "off"
xdebug.mode=off
EOF
else
cat <<- EOF
; Enabled selected modes
xdebug.mode=${XDEBUG_MODES}
EOF
fi
