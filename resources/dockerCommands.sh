#!/usr/bin/bash
echo 'Refreshing package list'
echo "Server = https://archlinux.mailtunnel.eu/\$repo/os/\$arch" >> /etc/pacman.d/mirrorlist
echo "[chindit]" >> /etc/pacman.conf
echo "SigLevel = Optional TrustAll" >> /etc/pacman.conf
echo "Server=http://mirror.chindit.eu" >> /etc/pacman.conf
pacman -Syu --noconfirm
echo 'Installing build utils'
pacman -S base-devel wget yay --noconfirm
echo 'Creating build directory and setting rights'
useradd -d /home/packager -G root -m packager
echo "packager ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers
chown -R packager:users /home/packager
cd /home/packager/ || exit
echo 'Copying files'
cp /tmp/package/* .
echo 'Installing build dependencies'
echo 'sudo -u packager yay -S --noconfirm {buildDependencies}'
sudo -u packager yay -S --noconfirm {buildDependencies}
echo 'Starting build'
sudo -u packager makepkg -s --noconfirm
echo 'Moving back package to exchange directory'
mv *.tar.xz /tmp/package/
