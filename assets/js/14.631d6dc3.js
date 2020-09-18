(window.webpackJsonp=window.webpackJsonp||[]).push([[14],{368:function(t,a,s){"use strict";s.r(a);var e=s(42),n=Object(e.a)({},(function(){var t=this,a=t.$createElement,s=t._self._c||a;return s("ContentSlotsDistributor",{attrs:{"slot-key":t.$parent.slotKey}},[s("h1",{attrs:{id:"deploying-artifacts"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#deploying-artifacts"}},[t._v("#")]),t._v(" Deploying artifacts")]),t._v(" "),s("p",[t._v("If you want to deploy artifacts, phabalicious can help you here, currently there are two methods supported:")]),t._v(" "),s("ul",[s("li",[t._v("artifacts--git")]),t._v(" "),s("li",[t._v("artifacts--ftp")])]),t._v(" "),s("p",[t._v("Both methods create a new application from scratch and deploy all or parts of it to a ftp server or store the artifacts in a git repository. Both methods are using the same stage mechanisms as in "),s("code",[t._v("app:create")]),t._v(".")]),t._v(" "),s("p",[t._v("Both methods needs the global "),s("code",[t._v("repository")]),t._v("-config, so they can pull the app into a new temporary folder.")]),t._v(" "),s("h2",{attrs:{id:"artifacts-ftp"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#artifacts-ftp"}},[t._v("#")]),t._v(" artifacts--ftp")]),t._v(" "),s("p",[t._v("ftp will create a new application in a temporary folder, install all needed dependencies, copies the data into a new temporary folder and run the deploy script of the host-config. After that is finished, phab will mirror all changed files via "),s("code",[t._v("lftp")]),t._v(" to the server. "),s("code",[t._v("lftp")]),t._v(" supports multiple protocols, not only ftp, but it needs to be installed on the machine running phab.")]),t._v(" "),s("p",[t._v("ftp-sync use the following stages:")]),t._v(" "),s("ul",[s("li",[s("code",[t._v("installCode")]),t._v(" will most of the time clone the source repository")]),t._v(" "),s("li",[s("code",[t._v("installDependencies")]),t._v(" will install all needed dependencies")]),t._v(" "),s("li",[s("code",[t._v("runActions")]),t._v(" will run all defined actions (see below)")]),t._v(" "),s("li",[s("code",[t._v("runDeployScript")]),t._v(" run the deploy-script of the host to apply some custom changes")]),t._v(" "),s("li",[s("code",[t._v("syncToFtp")]),t._v(" sync all changed files with the remote ftp server.")])]),t._v(" "),s("h3",{attrs:{id:"example-config"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#example-config"}},[t._v("#")]),t._v(" Example config")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[t._v("\n"),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("hosts")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("ftp-artifacts")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("needs")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" git\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" artifacts"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("ftp\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" script\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("artifact")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("user")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" stephan\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("host")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" sftp"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("//localhost\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("port")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token number"}},[t._v("22")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("password")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" my"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("secret\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("rootFolder")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" /home/stephan/somewhere\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("actions")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" copy\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("from")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token string"}},[t._v('"*"')]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("to")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" .\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" script\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" cp .env.example .env\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" delete\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .git/\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .fabfile\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .editorconfig\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .env.example\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .gitattributes\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .gitignore\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer.lock\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer.json\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" docker"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("compose.yml\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" script\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" ls "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("la\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" confirm\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("question")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" Do you want to continue"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("?")]),t._v("\n\n")])])]),s("p",[t._v("The default actions for the ftp-artifact-method will copy all files to the target repo and remove the "),s("code",[t._v(".git")]),t._v("-folder and the fabfile.")]),t._v(" "),s("h2",{attrs:{id:"artifacts-git"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#artifacts-git"}},[t._v("#")]),t._v(" artifacts--git")]),t._v(" "),s("p",[t._v("This method will create the artifact, pull the target repository, copy all necessary files over to the target repository, commit any changes to the target repository and push the changes again. A CI listening to commits can do the actual deployment")]),t._v(" "),s("p",[t._v("It is using the following stages:")]),t._v(" "),s("ul",[s("li",[s("code",[t._v("installCode")]),t._v(", creates a temporary folder and pulls the source repository. (only when "),s("code",[t._v("useLocalRepository")]),t._v(" is set to false)")]),t._v(" "),s("li",[s("code",[t._v("installDependencies")]),t._v(" to install the dependencies")]),t._v(" "),s("li",[s("code",[t._v("getSourceCommitInfo")]),t._v(" get the commit hash from the source repo.")]),t._v(" "),s("li",[s("code",[t._v("runActions")]),t._v(" will run all defined actions (see below)")]),t._v(" "),s("li",[s("code",[t._v("copyFilesToTargetDirectory")]),t._v(", copy specified "),s("code",[t._v("files")]),t._v(" to the target directory, removes all files listed in "),s("code",[t._v("excludeFiles.gitSync")])]),t._v(" "),s("li",[s("code",[t._v("runDeployScript")]),t._v(" run the deploy script of the host-config.")]),t._v(" "),s("li",[s("code",[t._v("pushToTargetRepository")]),t._v(" commit and push all changes, using the changelog as a commit-message")])]),t._v(" "),s("p",[t._v("If you run phabalicious as part of a CI-setup, it might make sense to set "),s("code",[t._v("useLocalRepository")]),t._v(" to true, as this will instruct phab to use the current folder as a base for the artifact and won't create a new application in another temporary folder.")]),t._v(" "),s("h3",{attrs:{id:"example-config-2"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#example-config-2"}},[t._v("#")]),t._v(" Example config")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("hosts")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("git-artifact")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("needs")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" git\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" artifacts"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("git\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" script\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("artifact")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("branch")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" master\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("repository")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" ssh"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("//somewhere/repository.git\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("useLocalRepository")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token boolean important"}},[t._v("false")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("actions")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" copy\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("from")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token string"}},[t._v("'*'")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("to")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" .\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" script\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" cp .env.example .env\n        "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" delete\n          "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .env.example\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer.json\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" composer.lock\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" docker"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("compose.yml\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" docker"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("compose"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v("mbb.yml\n            "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" .projectCreated\n")])])]),s("p",[t._v("the default actions for the git-artifact-method will copy all files to the target repo and remove the fabfile.")]),t._v(" "),s("h3",{attrs:{id:"artifacts-custom"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#artifacts-custom"}},[t._v("#")]),t._v(" artifacts--custom")]),t._v(" "),s("p",[t._v("@TODO")]),t._v(" "),s("h2",{attrs:{id:"available-actions"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#available-actions"}},[t._v("#")]),t._v(" Available actions")]),t._v(" "),s("p",[t._v("You can customize the list of actions be run when deploying an artifact. Here's a list of available actions")]),t._v(" "),s("h3",{attrs:{id:"copy"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#copy"}},[t._v("#")]),t._v(" copy")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" copy\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("argumnents")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("from")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" file1\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" folder2\n      "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" subfolder/file3\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("to")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" targetsubfolder\n")])])]),s("p",[t._v("This will copy the three mentioned files and folders into the subfolder "),s("code",[t._v("targetsubfolder")]),t._v(" of the target folder. Please be aware, that you might need to create subdirectories beforehand manually via the "),s("code",[t._v("script")]),t._v("-method")]),t._v(" "),s("h3",{attrs:{id:"delete"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#delete"}},[t._v("#")]),t._v(" delete")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" delete\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" file1\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" folder2\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" subfolder/file3\n")])])]),s("p",[t._v("This action will delete the list of files and folders in the target folder. Here you can clean up the target and get rid of unneeded files.")]),t._v(" "),s("h3",{attrs:{id:"exclude"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#exclude"}},[t._v("#")]),t._v(" exclude")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" exclude\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" file1\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" folder2\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" subfolder/file3\n")])])]),s("p",[t._v("Similar to "),s("code",[t._v("delete")]),t._v(" this will exclude the list of file and folders from be transferred to the target. For "),s("code",[t._v("ftp")]),t._v(" the list of files get excluded from transferring, for "),s("code",[t._v("git")]),t._v(" they will get resetted from the target repository.")]),t._v(" "),s("h3",{attrs:{id:"confirm"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#confirm"}},[t._v("#")]),t._v(" confirm")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" confirm\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("question")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" Do you want to continue"),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("?")]),t._v("\n")])])]),s("p",[t._v("This action comes handy when degugging the build process, as it will stop the execution and asks the user the questions and wait for "),s("code",[t._v("yes")]),t._v(" before continuing. Answering sth different will cancel the further execution.")]),t._v(" "),s("h3",{attrs:{id:"script"}},[s("a",{staticClass:"header-anchor",attrs:{href:"#script"}},[t._v("#")]),t._v(" script")]),t._v(" "),s("div",{staticClass:"language-yaml extra-class"},[s("pre",{pre:!0,attrs:{class:"language-yaml"}},[s("code",[s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("action")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v(" script\n  "),s("span",{pre:!0,attrs:{class:"token key atrule"}},[t._v("arguments")]),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(":")]),t._v("\n    "),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(' echo "Hello world"\n    '),s("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("-")]),t._v(" cp .env.production .env\n")])])]),s("p",[t._v("The "),s("code",[t._v("script")]),t._v("-action will run the script from the arguments section line by line. You can use the usual replacement patterns as for other scripts. Most helpful are:")]),t._v(" "),s("table",[s("thead",[s("tr",[s("th",[t._v("Pattern")]),t._v(" "),s("th",[t._v("Description")])])]),t._v(" "),s("tbody",[s("tr",[s("td",[s("code",[t._v("%context.data.installDir%")])]),t._v(" "),s("td",[t._v("The installation dir, where the app got installed into")])]),t._v(" "),s("tr",[s("td",[s("code",[t._v("%context.data.targetDir%")])]),t._v(" "),s("td",[t._v("The targetdir, where the app got copied to, which gets committed or synced")])])])])])}),[],!1,null,null,null);a.default=n.exports}}]);