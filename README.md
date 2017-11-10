# builder
tools for building 32-bit archlinux packages from archlinux.org's official, 64-bit tested PKGBUILDs et al.
This includes scripts to be run on the build master as well as scripts to be run on the build slaves (both residing in `bin`).

## requirements
* `moreutils`
### build master only
* some ssh-server
* `git`
* `pkgbuild-introspection`
* `bc`
### build slave only
* some ssh-client
* `wget`
* `sudo` rights for `staging-with-build-support-i686-build`, `staging-i686-build`, `multilib-build`, `extra-x86_64-build`
* `devtools32`

## configuration
The standard configuration in `conf/default.conf` can be locally overwritten by `conf/local.conf`.
### build master only
* add `command=".../bin/slave-build-connect $slave-identifier" $ssh-key` to `~/.ssh/authorized_keys` for each build slave
### build slave only
* set `keyserver-options auto-key-retrieve` in ~/.gnupg/gpg.conf
* put an i686 mirror into `/etc/pacman.d/mirrorlist` as __first__ mirror

## tools for the build master
* `build-master-status`:
Print some informational statistics.
* `build-slave-connect`:
Proxy command to be allowed for connection via ssh from build slaves - this way, they can execute exactly the commands they need to operate.
* `calculate-dependent-packages`:
Calculate how many packages on the build list depend on each package on the build list.
* `cleanup`:
Clean up left over files.
* `db-update`:
Move around packages on the master mirror.
* `get-assignment`:
Receive a build assignment from the `build-list`.
* `get-package-updates`:
Update the `build-list`.
* `interpret-mail`:
Interpret the content of an email - also checks for validity of the mail.
* `prioritize-build-list`:
Reorder the build list.
* `return-assignment`:
Return an assignment - either a tar of built package(s) or of error logs.
* `sanity-check`:
Check sanity of build master.
* `seed-build-list`:
Seed the build list from an upstream mirror or a manual package list.
* `show-dependencies`:
Generate graphs of dependencies between build-list packages for the web server.
* `why-dont-you`:
Script to investigate why a (desired) action is not done.

## tools for the build slaves
* `build-packages`:
Get a build assignment from the build master, build it and report back.
* `clean-cache`:
Remove packages from /var/cache/archbuild32 which do not match their checksum.

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
