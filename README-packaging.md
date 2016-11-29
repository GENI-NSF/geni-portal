# Building a package for RedHat/CentOS

The geni-portal distribution includes the information needed to build an
rpm package. In order to build the package you must first install
the rpm packaging tools. On CentOS 7, the tools can be
installed with the following commands:

```sh
sudo yum install -y rpm-build rpmdevtools rpmlint createrepo
sudo yum groupinstall -y "Development Tools"
sudo yum install -y httpd texinfo
```

As a regular user (not root), set up an rpm build area:

```
rpmdev-setuptree
```

Download the geni-ch tar file. Check for the file on the releases tab at
the [GitHub project page](https://github.com/GENI-NSF/geni-portal).

Alternatively, instead of downloading the geni-ch tar file, you can create
it as follows:

```
git clone https://github.com/GENI-NSF/geni-portal.git
cd geni-portal

# Optionally checkout a branch other than the default

./autogen.sh
./configure
make dist

# Replace version number below with current version number
VERSION=3.19

cp geni-ch-${VERSION}.tar.gz ~/rpmbuild/SOURCES
rpmbuild -ba geni-portal.spec
```

Once the tar file has been downloaded or created,
follow these steps to build the package:

```
VERSION=3.19
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
