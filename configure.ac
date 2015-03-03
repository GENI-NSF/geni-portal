AC_INIT([geni-ch], [2.28], [portal-help@geni.net])
#AM_INIT_AUTOMAKE([foreign -Wall -Werror])
#AM_INIT_AUTOMAKE([foreign -Wall])
AM_INIT_AUTOMAKE([foreign -Wall -Wno-portability])
AC_PROG_MKDIR_P
AC_PROG_INSTALL
AM_PATH_PYTHON([2.6])

dnl Check for Apache user
apache_user=no
AC_MSG_CHECKING(for apache user)
AC_ARG_WITH(apache_user,[  --with-apache-user=<user> sets path Apache runs with],apache_user=$withval,apache_user=no)
if test x$apache_user = xno; then
if grep -e "^apache:" /etc/passwd >/dev/null ; then
	# RedHat, Fedora, CentOS
	apache_user="apache"
elif grep -e "^www-data:" /etc/passwd >/dev/null ; then
	# Debian, Ubuntu, derivates of them
	apache_user="www-data"
else
        AC_MSG_ERROR([cannot determine apache user, use --with-apache-user])
fi
fi
AC_MSG_RESULT($apache_user)
AC_SUBST(apache_user)

dnl Check for Apache group
apache_group=no
AC_MSG_CHECKING(for apache group)
AC_ARG_WITH(apache_group,[  --with-apache-group=<group> sets path Apache runs with],apache_group=$withval,apache_group=no)
if test x$apache_group = xno; then
if grep -e "^apache:" /etc/group >/dev/null ; then
	# RedHat, Fedora, CentOS
	apache_group="apache"
elif grep -e "^www-data:" /etc/group >/dev/null ; then
	# Debian, Ubuntu, derivates of them
	apache_group="www-data"
else
        AC_MSG_ERROR([cannot determine apache group, use --with-apache-group])
fi
fi
AC_MSG_RESULT($apache_group)
AC_SUBST(apache_group)

AC_CONFIG_FILES([Makefile etc/Makefile bin/Makefile])
AC_CONFIG_FILES([sr/Makefile lib/Makefile cs/Makefile])
AC_CONFIG_FILES([logging/Makefile ma/Makefile pa/Makefile sa/Makefile])
AC_CONFIG_FILES([kmtool/Makefile portal/Makefile openid/Makefile])
AC_CONFIG_FILES([ca/Makefile sbin/Makefile ch/Makefile man/Makefile])
AC_OUTPUT
