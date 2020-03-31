#!/usr/bin/bash
echo 'Refreshing package list'
echo "Server = https://archlinux.mailtunnel.eu/$repo/os/$arch" >> /etc/pacman.d/mirrorlist
pacman -Sy
echo 'Installing build utils'
pacman -S base-devel wget --noconfirm
echo 'Creating build directory and setting rights'
useradd -d /home/packager -G root -m packager
echo "packager ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers
chown -R packager:users /home/packager
cd /home/packager/ || exit
echo 'Copying PKGBUILD'
cp /tmp/package/PKGBUILD .
echo 'Starting build'
sudo -u packager makepkg -s --noconfirm
echo 'Moving back package to exchange directory'
mv *.tar.xz /tmp/package/
