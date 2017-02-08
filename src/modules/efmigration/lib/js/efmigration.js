'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

(function ($, React, ReactDOM) {
    var STEP_STATUS_IDLE = 'idle';
    var STEP_STATUS_RUNNING = 'running';
    var STEP_STATUS_SUCCESS = 'success';
    var STEP_STATUS_ERROR = 'error';

    var StepRow = function (_React$Component) {
        _inherits(StepRow, _React$Component);

        function StepRow() {
            _classCallCheck(this, StepRow);

            return _possibleConstructorReturn(this, (StepRow.__proto__ || Object.getPrototypeOf(StepRow)).apply(this, arguments));
        }

        _createClass(StepRow, [{
            key: 'render',
            value: function render() {
                var id = 'pp-step-' + this.props.name;
                var className = 'pp-status-' + this.props.status;

                return React.createElement(
                    'li',
                    { id: id, className: className },
                    this.props.label
                );
            }
        }]);

        return StepRow;
    }(React.Component);

    var ErrorRow = function (_React$Component2) {
        _inherits(ErrorRow, _React$Component2);

        function ErrorRow() {
            _classCallCheck(this, ErrorRow);

            return _possibleConstructorReturn(this, (ErrorRow.__proto__ || Object.getPrototypeOf(ErrorRow)).apply(this, arguments));
        }

        _createClass(ErrorRow, [{
            key: 'render',
            value: function render() {
                return React.createElement(
                    'div',
                    { className: 'error' },
                    React.createElement(
                        'p',
                        null,
                        this.props.msg
                    )
                );
            }
        }]);

        return ErrorRow;
    }(React.Component);

    var StepList = function (_React$Component3) {
        _inherits(StepList, _React$Component3);

        function StepList() {
            _classCallCheck(this, StepList);

            return _possibleConstructorReturn(this, (StepList.__proto__ || Object.getPrototypeOf(StepList)).apply(this, arguments));
        }

        _createClass(StepList, [{
            key: 'render',
            value: function render() {
                var finished = this.props.finished;
                var steps = this.props.steps;
                var errors = this.props.errors;
                var started = this.props.started;
                var hasErrors = errors.length > 0;
                var inProgress = started && !finished;

                var stepRows = steps.map(function (step) {
                    return React.createElement(StepRow, {
                        key: step.key,
                        name: step.key,
                        status: step.status,
                        label: step.label });
                });

                var errorRows = errors.map(function (error) {
                    return React.createElement(ErrorRow, { key: error.key, msg: error.msg });
                });

                return React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'div',
                        { className: 'pp-progressbar-container' },
                        React.createElement(
                            'p',
                            null,
                            objectL10n.what_will_migrate,
                            ':'
                        ),
                        React.createElement(
                            'ul',
                            { className: 'pp-progressbar' },
                            stepRows
                        )
                    ),
                    inProgress && React.createElement(
                        'p',
                        null,
                        objectL10n.header_msg
                    ),
                    hasErrors && React.createElement(
                        'div',
                        { className: 'pp-errors' },
                        React.createElement(
                            'h2',
                            null,
                            objectL10n.error
                        ),
                        React.createElement(
                            'div',
                            null,
                            errorRows
                        ),
                        React.createElement(
                            'p',
                            null,
                            objectL10n.error_msg_intro,
                            ' ',
                            React.createElement(
                                'a',
                                { href: 'mailto:help@pressshack.com' },
                                objectL10n.error_msg_contact
                            )
                        )
                    ),
                    finished && React.createElement(
                        'div',
                        null,
                        React.createElement(
                            'p',
                            null,
                            objectL10n.success_msg
                        ),
                        React.createElement(
                            'a',
                            { href: objectL10n.back_to_publishpress_url },
                            objectL10n.back_to_publishpress_label
                        )
                    )
                );
            }
        }]);

        return StepList;
    }(React.Component);

    var StepListContainer = function (_React$Component4) {
        _inherits(StepListContainer, _React$Component4);

        function StepListContainer() {
            _classCallCheck(this, StepListContainer);

            return _possibleConstructorReturn(this, (StepListContainer.__proto__ || Object.getPrototypeOf(StepListContainer)).apply(this, arguments));
        }

        _createClass(StepListContainer, [{
            key: 'render',
            value: function render() {
                return React.createElement(StepList, { steps: this.props.steps, started: this.props.started, finished: this.props.finished, errors: this.props.errors });
            }
        }]);

        return StepListContainer;
    }(React.Component);

    var MigrationForm = function (_React$Component5) {
        _inherits(MigrationForm, _React$Component5);

        function MigrationForm() {
            _classCallCheck(this, MigrationForm);

            var _this5 = _possibleConstructorReturn(this, (MigrationForm.__proto__ || Object.getPrototypeOf(MigrationForm)).call(this));

            _this5.state = {
                steps: [{
                    key: 'options',
                    label: objectL10n.options,
                    status: STEP_STATUS_IDLE,
                    error: null
                }, {
                    key: 'usermeta',
                    label: objectL10n.usermeta,
                    status: STEP_STATUS_IDLE,
                    error: null
                }],
                currentStepIndex: -1,
                finished: false,
                errors: []
            };

            _this5.eventStartMigration = _this5.eventStartMigration.bind(_this5);
            return _this5;
        }

        _createClass(MigrationForm, [{
            key: 'executeNextStep',
            value: function executeNextStep() {
                var _this6 = this;

                // Go to the next step index.
                this.setState({ currentStepIndex: this.state.currentStepIndex + 1 }, function () {
                    // Check if we finished the step list to finish the process.
                    if (_this6.state.currentStepIndex >= _this6.state.steps.length) {

                        var _data = {
                            'action': 'pp_finish_migration',
                            '_wpnonce': objectL10n.wpnonce
                        };

                        $.post(ajaxurl, _data, function (response) {
                            _this6.setState({ finished: true });
                        });

                        return;
                    }

                    // We have a step. Lets execute it.
                    var currentStep = _this6.state.steps[_this6.state.currentStepIndex];

                    // Set status of step in progress
                    currentStep.status = STEP_STATUS_RUNNING;
                    _this6.updateStep(currentStep);

                    // Call the method to migrate and wait for the response
                    var data = {
                        'action': 'pp_migrate_ef_data',
                        'step': currentStep.key,
                        '_wpnonce': objectL10n.wpnonce
                    };
                    $.post(ajaxurl, data, function (response) {
                        var step = _this6.state.steps[_this6.state.currentStepIndex];

                        if (typeof response.error === 'string') {
                            // Error
                            step.status = STEP_STATUS_ERROR;
                            _this6.appendError('[' + step.key + '] ' + response.error);
                        } else {
                            // Success
                            step.status = STEP_STATUS_SUCCESS;
                        }

                        _this6.updateStep(step);
                        _this6.executeNextStep();
                    }, 'json').error(function (response) {
                        var step = _this6.state.steps[_this6.state.currentStepIndex];

                        step.status = STEP_STATUS_ERROR;
                        _this6.appendError('[' + step.key + '] ' + response.status + ': ' + response.statusText);

                        _this6.updateStep(step);
                        _this6.executeNextStep();
                    });
                });
            }
        }, {
            key: 'updateStep',
            value: function updateStep(newStep) {
                var index = this.state.currentStepIndex;

                var newSteps = this.state.steps.map(function (step) {
                    return step.key === newStep.key ? newStep : step;
                });

                this.setState({ steps: newSteps });
            }
        }, {
            key: 'appendError',
            value: function appendError(msg) {
                var errors = this.state.errors;
                errors.push({ key: errors.length, msg: msg });

                this.setState({ errors: errors });
            }
        }, {
            key: 'eventStartMigration',
            value: function eventStartMigration() {
                this.executeNextStep();
            }
        }, {
            key: 'render',
            value: function render() {
                var started = this.state.currentStepIndex > -1;

                return React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'div',
                        { className: 'updated' },
                        React.createElement(
                            'p',
                            null,
                            objectL10n.migration_warning
                        )
                    ),
                    React.createElement(
                        'div',
                        null,
                        React.createElement(StepListContainer, { steps: this.state.steps, started: started, finished: this.state.finished, errors: this.state.errors }),
                        !started && React.createElement(
                            'button',
                            { onClick: this.eventStartMigration, className: 'button button-primary' },
                            objectL10n.start_migration
                        )
                    )
                );
            }
        }]);

        return MigrationForm;
    }(React.Component);

    ReactDOM.render(React.createElement(MigrationForm, null), document.getElementById('pp-content'));
})(jQuery, React, ReactDOM);