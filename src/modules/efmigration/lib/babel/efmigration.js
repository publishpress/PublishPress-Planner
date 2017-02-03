(function($, React, ReactDOM) {
    const STEP_STATUS_WAITING = 'waiting';
    const STEP_STATUS_IN_PROGRESS = 'in_progress';
    const STEP_STATUS_SUCCESS = 'success';
    const STEP_STATUS_ERROR = 'error';
    const STEP_COUNT = 3;

    class StepList extends React.Component {
        constructor(props) {
            super(props);
        }

        render() {
            const finished = this.props.finished;
            const successMsg = objectL10n.success_msg;

            return (
                <div>
                    <div className="pp-progressbar-container">
                        <ul className="pp-progressbar">
                            {this.props.steps.map(this.renderStep)}
                        </ul>
                    </div>
                    <span className="dashicons dashicons-yes"></span>

                    {finished &&
                        <div>{successMsg}</div>
                    }
                </div>
            );
        }

        renderStep({name, label, status, error}) {
            const id = 'pp-step-' + name;
            const className = 'pp-status-' + status;

            return (
                <li id={id} className={className}>
                    {label}
                </li>
            );
        }
    }

    class StepListContainer extends React.Component {
        constructor() {
            super();

            this.state = {
                stepOptions: {
                    name: 'options',
                    label: objectL10n.options,
                    status: STEP_STATUS_WAITING,
                    error: null
                },
                stepTaxonomy: {
                    name: 'taxonomy',
                    label: objectL10n.taxonomy,
                    status: STEP_STATUS_WAITING,
                    error: null
                },
                stepUserMeta: {
                    name: 'user-meta',
                    label: objectL10n.user_meta,
                    status: STEP_STATUS_WAITING,
                    error: null
                },
                finishedSteps: 0,
                finished: false,
                currentStep: null
            };
        }

        componentDidMount() {
            const stateList = ['stepOptions', 'stepTaxonomy', 'stepUserMeta'];

            stateList.map((stepAlias) => {
                var step = this.state[stepAlias];

                setTimeout(
                    () => {
                        step.status = STEP_STATUS_IN_PROGRESS;

                        var newState = {};
                        newState[stepAlias] = step;

                        this.setState(newState);
                    },
                    2000
                );

                setTimeout(
                    () => {
                        step.status = STEP_STATUS_SUCCESS;

                        const finishedSteps = ++this.state.finishedSteps;

                        var newState = {
                            finishedSteps: finishedSteps,
                            finished: finishedSteps == STEP_COUNT
                        };
                        newState[stepAlias] = step;

                        this.setState(newState);
                    },
                    4000
                );
            });
        }

        render() {
            const listOfSteps = [
                this.state.stepOptions,
                this.state.stepTaxonomy,
                this.state.stepUserMeta
            ];

            return <StepList steps={listOfSteps} finished={this.state.finished} />;
        }
    }

    ReactDOM.render(<StepListContainer />, document.getElementById('pp-content'));
})(jQuery, React, ReactDOM);
