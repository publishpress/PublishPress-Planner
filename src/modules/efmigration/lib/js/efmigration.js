'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

(function ($, React, ReactDOM) {
    var STEP_STATUS_WAITING = 'waiting';
    var STEP_STATUS_IN_PROGRESS = 'in_progress';
    var STEP_STATUS_SUCCESS = 'success';
    var STEP_STATUS_ERROR = 'error';
    var STEP_COUNT = 3;

    var StepList = function (_React$Component) {
        _inherits(StepList, _React$Component);

        function StepList(props) {
            _classCallCheck(this, StepList);

            return _possibleConstructorReturn(this, (StepList.__proto__ || Object.getPrototypeOf(StepList)).call(this, props));
        }

        _createClass(StepList, [{
            key: 'render',
            value: function render() {
                var finished = this.props.finished;
                var successMsg = objectL10n.success_msg;

                return React.createElement(
                    'div',
                    null,
                    React.createElement(
                        'div',
                        { className: 'pp-progressbar-container' },
                        React.createElement(
                            'ul',
                            { className: 'pp-progressbar' },
                            this.props.steps.map(this.renderStep)
                        )
                    ),
                    React.createElement('span', { className: 'dashicons dashicons-yes' }),
                    finished && React.createElement(
                        'div',
                        null,
                        successMsg
                    )
                );
            }
        }, {
            key: 'renderStep',
            value: function renderStep(_ref) {
                var name = _ref.name,
                    label = _ref.label,
                    status = _ref.status,
                    error = _ref.error;

                var id = 'pp-step-' + name;
                var className = 'pp-status-' + status;

                return React.createElement(
                    'li',
                    { id: id, className: className },
                    label
                );
            }
        }]);

        return StepList;
    }(React.Component);

    var StepListContainer = function (_React$Component2) {
        _inherits(StepListContainer, _React$Component2);

        function StepListContainer() {
            _classCallCheck(this, StepListContainer);

            var _this2 = _possibleConstructorReturn(this, (StepListContainer.__proto__ || Object.getPrototypeOf(StepListContainer)).call(this));

            _this2.state = {
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
            return _this2;
        }

        _createClass(StepListContainer, [{
            key: 'componentDidMount',
            value: function componentDidMount() {
                var _this3 = this;

                var stateList = ['stepOptions', 'stepTaxonomy', 'stepUserMeta'];

                stateList.map(function (stepAlias) {
                    var step = _this3.state[stepAlias];

                    setTimeout(function () {
                        step.status = STEP_STATUS_IN_PROGRESS;

                        var newState = {};
                        newState[stepAlias] = step;

                        _this3.setState(newState);
                    }, 2000);

                    setTimeout(function () {
                        step.status = STEP_STATUS_SUCCESS;

                        var finishedSteps = ++_this3.state.finishedSteps;

                        var newState = {
                            finishedSteps: finishedSteps,
                            finished: finishedSteps == STEP_COUNT
                        };
                        newState[stepAlias] = step;

                        _this3.setState(newState);
                    }, 4000);
                });
            }
        }, {
            key: 'render',
            value: function render() {
                var listOfSteps = [this.state.stepOptions, this.state.stepTaxonomy, this.state.stepUserMeta];

                return React.createElement(StepList, { steps: listOfSteps, finished: this.state.finished });
            }
        }]);

        return StepListContainer;
    }(React.Component);

    ReactDOM.render(React.createElement(StepListContainer, null), document.getElementById('pp-content'));
})(jQuery, React, ReactDOM);