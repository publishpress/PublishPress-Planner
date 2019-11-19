(function ($, React, ReactDOM) {
    const STEP_STATUS_IDLE = 'idle';
    const STEP_STATUS_RUNNING = 'running';
    const STEP_STATUS_SUCCESS = 'success';
    const STEP_STATUS_ERROR = 'error';

    class ErrorRow extends React.Component {
        render () {
            return (
                <div className="error">
                    <p>{this.props.msg}</p>
                </div>
            );
        }
    }

    class StepList extends React.Component {
        render () {
            const finished = this.props.finished;
            const errors = this.props.errors;
            const started = this.props.started;
            const hasErrors = errors.length > 0;
            const inProgress = started && !finished;

            var errorRows = errors.map((error) =>
                <ErrorRow key={error.key} msg={error.msg}/>
            );

            return (
                <div>
                    <div className="pp-progressbar-container">
                        {inProgress
                        &&
                        <div>
                            <span className="dashicons dashicons-update pp-rotating"></span>
                            <span className="pp-in-progress">{objectL10n.header_msg}</span>
                        </div>
                        }
                    </div>

                    {hasErrors
                    &&
                    <div className="pp-errors">
                        <h2>{objectL10n.error}</h2>
                        <div>
                            {errorRows}
                        </div>
                        <p>{objectL10n.error_msg_intro} <a
                            href="mailto:help@publishpress.com">{objectL10n.error_msg_contact}</a></p>
                    </div>
                    }

                    {finished
                    &&
                    <div>
                        <p className="pp-success">{objectL10n.success_msg}</p>

                        <a className="button"
                           href={objectL10n.back_to_publishpress_url}>{objectL10n.back_to_publishpress_label}</a>
                    </div>
                    }
                </div>
            );
        }
    }

    class StepListContainer extends React.Component {
        render () {
            return <StepList started={this.props.started} finished={this.props.finished} errors={this.props.errors}/>;
        }
    }

    class MigrationForm extends React.Component {
        constructor () {
            super();

            this.state = {
                steps: [
                    {
                        key: 'options',
                        label: objectL10n.options,
                        status: STEP_STATUS_IDLE,
                        error: null
                    },
                    {
                        key: 'usermeta',
                        label: objectL10n.usermeta,
                        status: STEP_STATUS_IDLE,
                        error: null
                    }
                ],
                currentStepIndex: -1,
                finished: false,
                errors: []
            };

            this.eventStartMigration = this.eventStartMigration.bind(this);
        }

        executeNextStep () {
            // Go to the next step index.
            this.setState({currentStepIndex: this.state.currentStepIndex + 1}, () => {
                // Check if we finished the step list to finish the process.
                if (this.state.currentStepIndex >= this.state.steps.length) {

                    const data = {
                        'action': 'pp_finish_migration',
                        '_wpnonce': objectL10n.wpnonce
                    };

                    $.post(ajaxurl, data, (response) => {
                        this.setState({finished: true});
                    });

                    return;
                }

                // We have a step. Lets execute it.
                var currentStep = this.state.steps[this.state.currentStepIndex];

                // Set status of step in progress
                currentStep.status = STEP_STATUS_RUNNING;
                this.updateStep(currentStep);

                // Call the method to migrate and wait for the response
                const data = {
                    'action': 'pp_migrate_ef_data',
                    'step': currentStep.key,
                    '_wpnonce': objectL10n.wpnonce
                };
                $.post(ajaxurl, data, (response) => {
                    var step = this.state.steps[this.state.currentStepIndex];

                    if (typeof response.error === 'string') {
                        // Error
                        step.status = STEP_STATUS_ERROR;
                        this.appendError('[' + step.key + '] ' + response.error);
                    } else {
                        // Success
                        step.status = STEP_STATUS_SUCCESS;
                    }

                    this.updateStep(step);
                    this.executeNextStep();
                }, 'json')
                    .error((response) => {
                        var step = this.state.steps[this.state.currentStepIndex];

                        step.status = STEP_STATUS_ERROR;
                        this.appendError('[' + step.key + '] ' + response.status + ': ' + response.statusText);

                        this.updateStep(step);
                        this.executeNextStep();
                    });
            });
        }

        updateStep (newStep) {
            var index = this.state.currentStepIndex;

            var newSteps = this.state.steps.map((step) => {
                return step.key === newStep.key ? newStep : step;
            });

            this.setState({steps: newSteps});
        }

        appendError (msg) {
            var errors = this.state.errors;
            errors.push({key: errors.length, msg: msg});

            this.setState({errors: errors});
        }

        eventStartMigration () {
            this.executeNextStep();
        }

        render () {
            const started = this.state.currentStepIndex > -1;

            return (
                <div>
                    <div>
                        <p>{objectL10n.intro_text}</p>
                    </div>

                    {!started
                    &&
                    <h4 className="pp-warning">{objectL10n.migration_warning}</h4>
                    }

                    <div>
                        <StepListContainer started={started} finished={this.state.finished} errors={this.state.errors}/>

                        <br/>

                        {!started
                        &&
                        <button onClick={this.eventStartMigration}
                                className="button button-primary">{objectL10n.start_migration}</button>
                        }
                    </div>
                </div>
            );
        }
    }

    ReactDOM.render(<MigrationForm/>, document.getElementById('pp-content'));
})(jQuery, React, ReactDOM);
