# Auto Invite plugin

A plugin to automatically invite a visitor into chat.

Based on idea of [this code](https://github.com/JustBlackBird/aggressive-messaging).

## Installation

1. Get the archive with the plugin sources. You can download it from the
[official site](https://mibew.org/plugins#mibew-autoinvite) or build the
plugin from sources.

2. Untar/unzip the plugin's archive.

3. Put files of the plugins to the `<Mibew root>/plugins`  folder.

4. (optional) Add plugins configs to "plugins" structure in
"`<Mibew root>`/configs/config.yml". If the "plugins" stucture looks like
`plugins: []` it will become:
    ```yaml
    plugins:
        "Mibew:AutoInvite": # Plugin's configurations are described below
            wait_time: 30
            strategy: random
            group: 0
    ```

5. Navigate to "`<Mibew Base URL>`/operator/plugin" page and enable the plugin.

## Plugin's configurations

The plugin can be configured with values in "`<Mibew root>`/configs/config.yml" file.

### config.wait_time

Type: `Integer`

Default: `60`

Specify time in seconds to wait before sending an invitation.

### config.strategy

Type: `String`

Default: `first`

Invitation strategy (i.e. how to choose an operator on behalf of whom
the invitation will be sent). At the moment there are two options
available: `first` (first available operator will be used) and `random`
(speaks for itself).

### config.group

Type: `Integer`

Default: 0

What group to choose operator from. Note that if there will be no
available operators from that group the invitation will not be sent.


## Build from sources

There are several actions one should do before use the latest version of the plugin from the repository:

1. Obtain a copy of the repository using `git clone`, download button, or another way.
2. Install [node.js](http://nodejs.org/) and [npm](https://www.npmjs.org/).
3. Install [Gulp](http://gulpjs.com/).
4. Install npm dependencies using `npm install`.
5. Run Gulp to build the sources using `gulp default`.

Finally `.tar.gz` and `.zip` archives of the ready-to-use Plugin will be available in `release` directory.

## License

[Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
