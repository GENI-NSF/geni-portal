#!/bin/bash
# Script to exit portal/clearinghouse out of 'sundown mode'
# Sundown mode means that new or existing slices (and thus slivers)
# cannot have expiration times that go beyond a specified date

sudo ~/proto-ch/bin/geni-manage-maintenance --clear-sundown

