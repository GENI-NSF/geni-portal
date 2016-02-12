# Building a package for RedHat/CentOS

The geni-portal distribution includes the information needed to build an
rpm package. In order to build the package you must first install
the rpm packaging tools. On CentOS 7, the tools can be
installed with the following commands:

```
yum install rpm-build rpmdevtools rpmlint
yum groupinstall "Development Tools"
```

As a regular user (not root), set up an rpm build area:

```
rpmdev-setuptree
```

Download the geni-ch tar file. Check for the file on the releases tab at
the [GitHub project page](https://github.com/GENI-NSF/geni-portal).

Once the tar file has been downloaded,
follow these steps to build the package:

```
VERSION=3.11
tar zxf geni-ch-${VERSION}.tar.gz
mv geni-ch-${VERSION}.tar.gz "${HOME}"/rpmbuild/SOURCES
mv geni-ch-${VERSION}/geni-portal.spec "${HOME}"/rpmbuild/SPECS
cd "${HOME}"/rpmbuild/SPECS
rpmbuild -ba geni-portal.spec
```

This will generate the following files:
 * The rpm: `"${HOME}"/rpmbuild/RPMS/noarch/geni-portal-${VERSION}-1.el7.noarch.rpm`
 * The source rpm: `"${HOME}"/rpmbuild/SRPMS/geni-portal-${VERSION}-1.el7.src.rpm`

# Creating a yum repository

Install the `createrepo` tool:

```
yum install createrepo
```

Create a repository directory and move the files into it:

```
mkdir repo
cd repo
mv "${HOME}"/rpmbuild/RPMS/noarch/geni-portal-${VERSION}-1.el7.noarch.rpm .
mv "${HOME}"/rpmbuild/SRPMS/geni-portal-${VERSION}-1.el7.src.rpm .
mv "${HOME}"/rpmbuild/SOURCES/geni-portal-${VERSION}.tar.gz .
mv "${HOME}"/rpmbuild/SPECS/geni-portal.spec .

```

Generate the repository metadata:

```
createrepo --database .
```

Copy this entire directory to the repository server
(update the host and path as needed):

```
scp -r * repo.example.com:/path/centos/7/os/x86_64
```

Configure yum for the new repository by creating a file
in `/etc/yum.repos.d` named geni.repo with the following
contents (updating the host and path as needed):

```
[geni]
name = GENI software repository
baseurl = http://repo.example.com/path/centos/$releasever/os/$basearch/
```
