# builder
tools for building 32-bit archlinux packages from archlinux.org's official, 64-bit tested PKGBUILDs et al.
This includes scripts to be run on the build master as well as scripts to be run on the build slaves (both residing in `bin`).
The sources are currently hosted on https://git.archlinux32.org/archlinux32/builder.

## requirements
* `moreutils`
### build master only
* `base-devel`
* `bc`
* `git`
* `graphviz`
* `ii`
* `inotify-tools`
* some mysql-server
* `nginx` or equivalent
* `php-gd`
* `php-fpm` or equivalent
* `pkgfile`
* `rsync`
* `screen`
* some ssh-server
### build slave only
* some ssh-client
* `wget`
* `sudo` rights for `staging-with-build-support-i686-build`, `staging-i686-build`, `multilib-build`, `extra-x86_64-build`
* `devtools32`

## configuration
The standard configuration in `conf/*.conf.example` can be locally overwritten by removing the `.example` prefix, uncommenting the desired variables and changing the values.
### build master only
* add `command=".../bin/slave-build-connect $slave-identifier" $ssh-key` to `~/.ssh/authorized_keys` for each build slave
### build slave only
* set `keyserver-options auto-key-retrieve` in ~/.gnupg/gpg.conf
* put an i686 mirror into `/etc/pacman.d/mirrorlist` as __first__ mirror

## tools for the build master and the build slaves
* `check-opcodes`:
Scan binaries for certain opcodes.
* `clean-git`:
Clean the packages' git repositories.
* `opcode`:
Helper for `check-opcodes`.
* `strict-bashism-check`:
Strict style check for this code base.

## tools for the build master
* `bootstrap-mysql`:
Bootstrap the mysql database.
* `build-master-status`:
Print some informational statistics.
* `check-bugtracker`:
Receive list of packages with issues from the bug tracker.
* `check-db-structure`:
Dump the structure of the database.
* `cleanup`:
Clean up left over files.
* `copy-to-build-support`:
Copy a package into [build-support].
* `db-update`:
Move around packages on the master mirror.
* `delete-packages`:
Delete obsolete packages on the master mirror.
* `filter-build-logs`:
Filter content of build-logs for display on the webserver.
* `find-obsolete-packages`:
Find packages which are no longer available upstream.
* `get-assignment`:
Receive a build assignment from the `build-list`.
* `get-package-updates`:
Update the `build-list`.
* `ii-answer`:
Answer the irc channel.
* `ii-connect`:
Connect to and watch the irc channel.
* `interpret-mail`:
Interpret the content of an email - also checks for validity of the mail.
* `modify-package-state`:
Modify status of a package in the database.
* `ping-from-slave`:
Receive ping from slave.
* `prioritize-build-list`:
Reorder the build list.
* `repo-copy`:
Step brother of `repo-add` and `repo-remove`.
* `return-assignment`:
Return an assignment - either a tar of built package(s) or of error logs.
* `sanity-check`:
Check sanity of build master.
* `seed-build-list`:
Seed the build list from an upstream mirror or a manual package list.
* `show-dependencies`:
Generate graphs of dependencies between build-list packages for the web server.
* `slave-build-connect`:
Proxy command to be allowed for connection via ssh from build slaves - this way, they can execute exactly the commands they need to operate.
* `why-dont-you`:
Script to investigate why a (desired) action is not done.
* `wtf`:
Find which package contains a given file.

## tools for the build slaves
* `build-packages`:
Get a build assignment from the build master, build it and report back.
* `clean-cache`:
Remove packages from /var/cache/archbuild32 which do not match their checksum.
* `ping-to-master`:
Ping the build master to show the slave is still compiling.

## working directory
In the standard configuration, the directory `work` will be used to cache some volatile data, as well as the git repositories of PKGBUILDs and modifications (in `repos/packages`, `repos/community`, `repos/packages32`).
