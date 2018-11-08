'use strict';

var _createClass = function () {
    function defineProperties (target, props) {
        for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || false;
            descriptor.configurable = true;
            if ('value' in descriptor) descriptor.writable = true;
            Object.defineProperty(target, descriptor.key, descriptor);
        }
    }

    return function (Constructor, protoProps, staticProps) {
        if (protoProps) defineProperties(Constructor.prototype, protoProps);
        if (staticProps) defineProperties(Constructor, staticProps);
        return Constructor;
    };
}();

function _classCallCheck (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
        throw new TypeError('Cannot call a class as a function');
    }
}

function _possibleConstructorReturn (self, call) {
    if (!self) {
        throw new ReferenceError('this hasn\'t been initialised - super() hasn\'t been called');
    }
    return call && (typeof call === 'object' || typeof call === 'function') ? call : self;
}

function _inherits (subClass, superClass) {
    if (typeof superClass !== 'function' && superClass !== null) {
        throw new TypeError('Super expression must either be null or a function, not ' + typeof superClass);
    }
    subClass.prototype = Object.create(superClass && superClass.prototype, {
        constructor: {
            value: subClass,
            enumerable: false,
            writable: true,
            configurable: true
        }
    });
    if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
}

(function ($, React, ReactDOM) {
    var STEP_STATUS_IDLE = 'idle';
    var STEP_STATUS_RUNNING = 'running';
    var STEP_STATUS_SUCCESS = 'success';
    var STEP_STATUS_ERROR = 'error';

    var ErrorRow = function (_React$Component) {
        _inherits(ErrorRow, _React$Component);

        function ErrorRow () {
            _classCallCheck(this, ErrorRow);

            return _possibleConstructorReturn(this, (ErrorRow.__proto__ || Object.getPrototypeOf(ErrorRow)).apply(this, arguments));
        }

        _createClass(ErrorRow, [{
            key: 'render',
            value: function render () {
                return React.createElement(
                    'div',
                    {className: 'error'},
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

    var StepList = function (_React$Component2) {
        _inherits(StepList, _React$Component2);

        function StepList () {
            _classCallCheck(this, StepList);

            return _possibleConstructorReturn(this, (StepList.__proto__ || Object.getPrototypeOf(StepList)).apply(this, arguments));
        }

        _createClass(StepList, [{
            key: 'render',
            value: function render () {
                var finished = this.props.finished;
                var errors = this.props.errors;
                var started = this.props.started;
                var hasErrors = errors.length > 0;
                var inProgress = started && !finished;

                var errorRows = errors.map(function (error) {
                    return React.createElement(ErrorRow, {key: error.key, msg: error.msg});
                });

                return React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'div',
                        {className: 'pp-progressbar-container'},
                        inProgress && React.createElement(
                        'div',
                        null,
                        React.createElement('span', {className: 'dashicons dashicons-update pp-rotating'}),
                        React.createElement(
                            'span',
                            {className: 'pp-in-progress'},
                            objectL10n.header_msg
                        )
                        )
                    ),
                    hasErrors && React.createElement(
                    'div',
                    {className: 'pp-errors'},
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
                            {href: 'mailto:help@publishpress.com'},
                            objectL10n.error_msg_contact
                        )
                    )
                    ),
                    finished && React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'p',
                        {className: 'pp-success'},
                        objectL10n.success_msg
                    ),
                    React.createElement(
                        'a',
                        {className: 'button', href: objectL10n.back_to_publishpress_url},
                        objectL10n.back_to_publishpress_label
                    )
                    )
                );
            }
        }]);

        return StepList;
    }(React.Component);

    var StepListContainer = function (_React$Component3) {
        _inherits(StepListContainer, _React$Component3);

        function StepListContainer () {
            _classCallCheck(this, StepListContainer);

            return _possibleConstructorReturn(this, (StepListContainer.__proto__ || Object.getPrototypeOf(StepListContainer)).apply(this, arguments));
        }

        _createClass(StepListContainer, [{
            key: 'render',
            value: function render () {
                return React.createElement(StepList, {
                    started: this.props.started,
                    finished: this.props.finished,
                    errors: this.props.errors
                });
            }
        }]);

        return StepListContainer;
    }(React.Component);

    var MigrationForm = function (_React$Component4) {
        _inherits(MigrationForm, _React$Component4);

        function MigrationForm () {
            _classCallCheck(this, MigrationForm);

            var _this4 = _possibleConstructorReturn(this, (MigrationForm.__proto__ || Object.getPrototypeOf(MigrationForm)).call(this));

            _this4.state = {
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

            _this4.eventStartMigration = _this4.eventStartMigration.bind(_this4);
            return _this4;
        }

        _createClass(MigrationForm, [{
            key: 'executeNextStep',
            value: function executeNextStep () {
                var _this5 = this;

                // Go to the next step index.
                this.setState({currentStepIndex: this.state.currentStepIndex + 1}, function () {
                    // Check if we finished the step list to finish the process.
                    if (_this5.state.currentStepIndex >= _this5.state.steps.length) {

                        var _data = {
                            'action': 'pp_finish_migration',
                            '_wpnonce': objectL10n.wpnonce
                        };

                        $.post(ajaxurl, _data, function (response) {
                            _this5.setState({finished: true});
                        });

                        return;
                    }

                    // We have a step. Lets execute it.
                    var currentStep = _this5.state.steps[_this5.state.currentStepIndex];

                    // Set status of step in progress
                    currentStep.status = STEP_STATUS_RUNNING;
                    _this5.updateStep(currentStep);

                    // Call the method to migrate and wait for the response
                    var data = {
                        'action': 'pp_migrate_ef_data',
                        'step': currentStep.key,
                        '_wpnonce': objectL10n.wpnonce
                    };
                    $.post(ajaxurl, data, function (response) {
                        var step = _this5.state.steps[_this5.state.currentStepIndex];

                        if (typeof response.error === 'string') {
                            // Error
                            step.status = STEP_STATUS_ERROR;
                            _this5.appendError('[' + step.key + '] ' + response.error);
                        } else {
                            // Success
                            step.status = STEP_STATUS_SUCCESS;
                        }

                        _this5.updateStep(step);
                        _this5.executeNextStep();
                    }, 'json').error(function (response) {
                        var step = _this5.state.steps[_this5.state.currentStepIndex];

                        step.status = STEP_STATUS_ERROR;
                        _this5.appendError('[' + step.key + '] ' + response.status + ': ' + response.statusText);

                        _this5.updateStep(step);
                        _this5.executeNextStep();
                    });
                });
            }
        }, {
            key: 'updateStep',
            value: function updateStep (newStep) {
                var index = this.state.currentStepIndex;

                var newSteps = this.state.steps.map(function (step) {
                    return step.key === newStep.key ? newStep : step;
                });

                this.setState({steps: newSteps});
            }
        }, {
            key: 'appendError',
            value: function appendError (msg) {
                var errors = this.state.errors;
                errors.push({key: errors.length, msg: msg});

                this.setState({errors: errors});
            }
        }, {
            key: 'eventStartMigration',
            value: function eventStartMigration () {
                this.executeNextStep();
            }
        }, {
            key: 'render',
            value: function render () {
                var started = this.state.currentStepIndex > -1;

                return React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'div',
                        null,
                        React.createElement(
                            'p',
                            null,
                            objectL10n.intro_text
                        )
                    ),
                    !started && React.createElement(
                    'h4',
                    {className: 'pp-warning'},
                    objectL10n.migration_warning
                    ),
                    React.createElement(
                        'div',
                        null,
                        React.createElement(StepListContainer, {
                            started: started,
                            finished: this.state.finished,
                            errors: this.state.errors
                        }),
                        React.createElement('br', null),
                        !started && React.createElement(
                        'button',
                        {onClick: this.eventStartMigration, className: 'button button-primary'},
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
