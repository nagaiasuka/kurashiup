#!/usr/bin/env bash
set -euo pipefail

if command -v lftp >/dev/null 2>&1; then
  lftp --version | head -n 1
  exit 0
fi

echo 'Checking package-host DNS from this deployment runner'
for deploy_host in azure.archive.ubuntu.com archive.ubuntu.com security.ubuntu.com mirrors.edge.kernel.org; do
  if timeout 6 getent ahostsv4 "$deploy_host" >/dev/null; then
    echo "$deploy_host: DNS OK"
  else
    echo "$deploy_host: DNS lookup failed or timed out"
  fi
done

apt_network_options=(-o Acquire::Retries=0 -o Acquire::http::Timeout=10 -o Acquire::https::Timeout=10)
if timeout 90 sudo apt-get "${apt_network_options[@]}" -o APT::Update::Error-Mode=any update \
  && timeout 60 sudo apt-get "${apt_network_options[@]}" install -y lftp; then
  exit 0
fi

echo 'Default Ubuntu package hosts failed; trying the Linux Kernel Archives Ubuntu mirror'
. /etc/os-release
: "${VERSION_CODENAME:?Ubuntu version codename is required}"
deploy_sources=$(mktemp --suffix=.sources)
trap 'rm -f "$deploy_sources"' EXIT
cat > "$deploy_sources" <<EOF
Types: deb
URIs: https://mirrors.edge.kernel.org/ubuntu
Suites: ${VERSION_CODENAME} ${VERSION_CODENAME}-updates ${VERSION_CODENAME}-security
Components: main universe
Signed-By: /usr/share/keyrings/ubuntu-archive-keyring.gpg
EOF
chmod 644 "$deploy_sources"
fallback_options=(-o "Dir::Etc::sourcelist=$deploy_sources" -o Dir::Etc::sourceparts=- -o APT::Get::List-Cleanup=0)
timeout 120 sudo apt-get "${apt_network_options[@]}" "${fallback_options[@]}" -o APT::Update::Error-Mode=any update
timeout 90 sudo apt-get "${apt_network_options[@]}" "${fallback_options[@]}" install -y lftp
