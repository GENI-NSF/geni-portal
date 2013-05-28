#!/bin/bash
# Script to enter a portal/clearinghouse installation into 'lockdown mode'
# Lockdown mode means that existing users can continue to use existing
# slices and slivers but can't create new slices, renew existng slices
# create new projects
# download or upload crypto materials
# or make changes to properties of slices and projects including membership

psql -U portal -h localhost portal < /usr/share/geni-ch/cs/db/postgresql/enable_lockdown.sql

#$alert = "This GENI Portal/Clearinghouse is in lockdown mode. In this mode, you can use existing slices until they expire, but cannot create new projects or slices, change slice/project membership, register new members, upload new rspecs or upload/download new crypto materials."

alert="This GENI Clearingnouse and Portal has been transitioned \
to https://portal.geni.net. Please use the new site. Old data will be \
available read-only here until June 17th. At this site, you can no \
longer reserve resources, create or join projects, create slices, renew slices, edit \
projects, edit slices, ask to join projects, invite people to \
projects or to use GENI, change slice or project \
membership, register new accounts, upload or edit RSpecs, upload or \
edit or download SSH keys or SSL certificates, or add notes on projects \
or slices - including via tools other than this Portal."

sudo ~/proto-ch/bin/geni-manage-maintenance --set-alert $alert

sudo ~/proto-ch/bin/geni-manage-maintenance --set-lockdown

