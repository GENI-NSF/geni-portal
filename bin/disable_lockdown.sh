#!/bin/bash
# Script to exit a portal/clearinghouse installation out of 'lockdown mode'
# Lockdown mode means that existing users can continue to use existing
# slices and slivers but can't create new slices, renew existng slices
# create new projects
# download or upload crypto materials
# or make changes to properties of slices and projects including membership

psql -U portal -h localhost portal < /usr/share/geni-ch/cs/db/postgresql/disable_lockdown.sql

sudo ~/proto-ch/bin/geni-manage-maintenance --clear-alert

sudo ~/proto-ch/bin/geni-manage-maintenance --clear-lockdown

