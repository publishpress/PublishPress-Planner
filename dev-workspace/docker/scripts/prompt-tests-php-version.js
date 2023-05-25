#!/usr/bin/env node --experimental-modules

/*
 * Copyright (c) 2022. PublishPress, All rights reserved.
 */

import { promptUser, executeCommand } from './modules/prompt-user.mjs';

const additionalArgs = process.argv.slice(2);

promptUser()
    .then((answers) => {
        const command = additionalArgs.join(' ');
        executeCommand(command, answers.version);
    })
    .catch((error) => {
        console.error(error);
    });
