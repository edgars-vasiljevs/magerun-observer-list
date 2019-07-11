# Magerun Observer List

Magerun module for listing all Magento observers by event code.

![](http://i.imgur.com/X5NH8MD.png)

##Requirements
Magerun: https://github.com/netz98/n98-magerun

## Installation
1. Create `~/.n98-magerun/modules/`
2. Clone this repository to `~/.n98-magerun/modules/`

        cd ~/.n98-magerun/modules/
        git clone https://github.com/orkz/magerun-observer-list.git

## Usage

To list observers for specific event:

    $ magerun dev:observer-list [event]

To list all observers for all events:

    $ magerun dev:observer-list
    
To exclude Magento core observers:

    $ magerun dev:observer-list --exclude-core
