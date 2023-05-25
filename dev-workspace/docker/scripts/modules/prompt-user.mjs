import { execSync } from 'child_process';
import inquirer from 'inquirer';

const exec = commands => {
    execSync(commands, {stdio: 'inherit', shell: true});
};

export const promptUser = () => {
    return inquirer.prompt([
        {
            type: "list",
            name: "version",
            message: "Select a PHP version",
            choices: ["7.2", "7.4", "8.0", "8.1", "8.2"]
        },
    ]);
};

export const executeCommand = (command, version) => {
    const commandWithVersion = command.replace('{{VERSION}}', version);
    exec(commandWithVersion);
};
