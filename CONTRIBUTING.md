# Contributing to geni-portal

The GENI-NSF repositories are very much a community driven effort, and
your contributions are critical. A big thank you to all our contributors!

## Mailing Lists

* GENI experimenters can ask questions and get announcements on the
  [GENI Users Google group](https://groups.google.com/forum/#!forum/geni-users).
* Developers can discuss GENI development on the
  [GENI Developers Google group](https://groups.google.com/forum/#!forum/geni-developers).

## General Guidelines

* GENI-NSF projects follow the
  [GitHub open source project guidelines](https://guides.github.com/activities/contributing-to-open-source/#contributing).
* [Create a GitHub Issue](#reporting-issues) for any bug, feature, or
  enhancement you find or intend to address.
* Submit enhancements or bug fixes using pull requests
  (see the [sample workflow below](#sample-contribution-workflow)).
* GENI-NSF projects use the
  [GitHub Flow](https://guides.github.com/introduction/flow/) branching model.
* All GENI-NSF code is released under the [GENI Public License](LICENSE.txt)
  and should include that license.

## Reporting Issues ##
 - Check [existing issues](https://github.com/GENI-NSF/geni-portal/issues) first to see if the issue has already been reported.
 - Review the general [GitHub guidlines on isssues](https://guides.github.com/features/issues/).
 - Give specific examples, sample outputs, etc
 - Do not include any passwords, private keys, your `omni.bundle`, or any information you don't want public.
 - When reporting issues, please include screen shots (simply drag them in), a description of what you did, any request RSpec, etc.
 - To attach your test case RSpecs or other large output, upload the file to some web server and provide a pointer. For example, to use Gist:
  - Copy & paste your log/patch/file attachment to http://gist.github.com/, hit the `Create Public` button and link to it from your issue by copying & pasting its URL.

## Getting the Source
GENI Portal source code is available on [GitHub](https://github.com/GENI-NSF/geni-portal).

## Sample Contribution Workflow ##
 1. [Report the issue](#reporting-issues) or check issue comments for a suggested solution.
 2. Create an issue-specific branch off of the `master` branch in your [fork of the repository](http://guides.github.com/activities/forking/).
 3. Develop your fix.
  - Follow the [code guidelines below](#code-style).
  - Reference the appropriate issue numbers in your commit messages.
  - Include the [GENI Public License](LICENSE.txt) and a copyright notice in any new source files.
  - All changes should be listed in the [CHANGES](CHANGES) file, with an issue number.
 4. Test your fix
 5. [Pull in any new changes](https://help.github.com/articles/syncing-a-fork) from the main repository ('upstream' repository).
 6. [Submit a pull request](https://help.github.com/articles/using-pull-requests/) against the `master` branch of the project repository.
 - In your pull request description, note what issue(s) your pull request addresses.

## Code Style ##
 - Include the [GENI Public License](LICENSE.txt) as a comment at the top of all source files.
 - Document all files and key classes and methods.
 - Name classes, methods, arguments and variables to describe their use.

_Thank you for your contributions!_
