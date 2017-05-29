# builder
tools for building 32-bit archlinux packages from archlinux.org's official, 64-bit tested PKGBUILDs et al.
This includes scripts to be run on the build master as well as scripts to be run on the build slaves (both residing in `bin`).

## requirements
* `git`
### build master only
* some ssh-server
* `pkgbuild-introspection`
### build slave only
* some ssh-client
* `devtools32`

## configuration
The standard configuration in `conf/default.conf` can be locally overwritten by `conf/local.conf`.
### build master only
* add `command=".../bin/slave-build-connect $slave-identifier" $ssh-key` to `~/.ssh/authorized_keys` for each build slave
### build slave only
* set `keyserver-options auto-key-retrieve` in ~/.gnupg/gpg.conf
* put an i686 mirror into `/etc/pacman.d/mirrorlist` as __first__ mirror

## tools for the build master
* `get-assignment`:
Receive a build assignment from the `build-list`.
* `get-package-updates`:
Update the `build-list`.
* `build-slave-connect`:
Proxy command to be allowed for connection via ssh from build slaves - this way, they can execute exactly the commands they need to operate.

## tools for the build slaves
* `build-packages`:
Get a build assignment from the build master, build it and report back.

## working directory
In the standard configuration, the directory `work` will be used to cache the following data:
* `build-list`, `build-list.loops`, `build-order`, `tsort.error`:
order of builds of packages and dependency loops
* `deletion-list`:
packages to be deleted
* `*.revision`:
current revisions of the respective repository
* `package-infos`:
meta data of packages
* `package-states`:
information on build process of packages (lock files, markers for broken packages)
* `repos/packages`, `repos/community`, `repos/packages32`:
git repositories of PKGBUILDs and modifications
