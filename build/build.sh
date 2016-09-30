#!/bin/sh

set -e

export PATH='/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'

SCRIPTDIR="${0%/*}"
BUILDDIR=$(cd "$SCRIPTDIR"; pwd) # Same as SCRIPTDIR but with a full path.
PKGDIR="${BUILDDIR}/se3master"
UPDATENB='160'

# Cleaning of $BUILDDIR.
rm -f "${BUILDDIR}/"*.deb
rm -rf "$PKGDIR" && mkdir -p "$PKGDIR"

# Copy the source in the "$PKGDIR" directory. Copy all
# directories in the root of this repository except the
# "build/" directory itself.
for dir in "${BUILDDIR}/../"*
do
    # Convert to the full path.
    dir=$(readlink -f "$dir")

    [ ! -d "$dir" ]            && continue
    [ "$dir" = "${BUILDDIR}" ] && continue

    cp -ra "$dir" "$PKGDIR"
done

VERSION=$(grep -i '^version:' "${PKGDIR}/DEBIAN/control" | cut -d' ' -f2)

while true
do
    [ ! -e "${PKGDIR}/var/cache/se3_install/maj/maj${UPDATENB}.sh" ] && break
    UPDATENB=$((UPDATENB + 1))
done

sed -i -e "s/#VERSION#/${VERSION}/g" \
       -e "s/#MAJNBR#/${UPDATENB}/g" \
       "${PKGDIR}/var/cache/se3_install/se3db.sql"

echo "Version ${VERSION} du $(date)" > "${PKGDIR}/var/cache/se3_install/version"

chmod -R 755 "${PKGDIR}/DEBIAN"
chmod -R 750 "${PKGDIR}/var/cache/se3_install"
chmod 644    "${PKGDIR}/var/cache/se3_install/conf/"*
chmod 600    "${PKGDIR}/var/cache/se3_install/conf/SeConfig.ph.in"
chmod 600    "${PKGDIR}/var/cache/se3_install/conf/slapd_"*.in
# chmod 644    "${PKGDIR}/var/cache/se3_install/reg/"*                  <= not present in the repository?!
# chmod 755    "${PKGDIR}/var/cache/se3_install/conf/apachese"          <= not present in the repository?!
# chmod 600    "${PKGDIR}/var/cache/se3_install/conf/config.inc.php.in" <= not present in the repository?!
# chmod 640    "${PKGDIR}/var/cache/se3_install/conf/mrtg.cfg"          <= not present in the repository?!
# chmod 440    "${PKGDIR}/var/cache/se3_install/conf/sudoers"           <= not present in the repository?!

# dos2unix "${PKGDIR}/var/cache/se3_install/scripts/"*.sh               <= not present in the repository?!
# dos2unix "${PKGDIR}/var/cache/se3_install/scripts/"*.pl               <= not present in the repository?!
# dos2unix "${PKGDIR}/var/cache/se3_install/sudoscripts/"*.sh           <= not present in the repository?!
# dos2unix "${PKGDIR}/var/cache/se3_install/sudoscripts/"*.pl           <= not present in the repository?!

# Now, it's possible to build the package.
cd "$BUILDDIR" || {
    echo "Error, impossible to change directory to \"${BUILDDIR}\"." >&2
    echo "End of the script."                                        >&2
    exit 1
}
find "$PKGDIR" -name ".empty" -delete

dpkg --build "$PKGDIR"

echo "OK, building succesfully..."


