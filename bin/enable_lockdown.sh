#!/bin/bash
# Script to enter a portal/clearinghouse installation into 'lockdown mode'
# Lockdown mode means that existing users can continue to use existing
# slices and slivers but can't create new slices, renew existng slices
# create new projects
# download or upload crypto materials
# or make changes to properties of slices and projects including membership
psql -U portal -h localhost portal < /usr/share/geni-ch/cs/db/postgresql/enable_lockdown.sql
~/proto-ch/bin/geni-manage-maintenance --set-alert "This GENI Portal/Clearinghouse is in lockdown mode. In this mode, you can use existing slices until they expire, but cannot create new projects or slices, change slice/project membership, register new members, upload new rspecs or upload/download new crypto materials."
~/proto-ch/bin/geni-manage-maintenance --set-lockdown

