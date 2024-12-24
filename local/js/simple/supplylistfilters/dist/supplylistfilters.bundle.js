/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
    'use strict';

    var namespace = main_core.Reflection.namespace('BX.CRM.Kanban');
    if (Object.values(namespace).length > 0) {
      var proxy = new Proxy(BX.CRM.Kanban.Item.prototype, {
        get: function get(target, prop, receiver) {
          var parentFunction = target[prop];
          if (prop === 'layoutFields') {
            var data = receiver.getData();
            if (!data.fieldChanged) {
              var fields = data.fields;
              var vid = fields.find(function (x) {
                return x.code && x.code === 'UF_CRM_33_VID';
              });
              var isProductField = fields.find(function (x) {
                return x.code && x.code === 'UF_IS_PRODUCT';
              });
              var isProduct = isProductField && isProductField.value === "да";
              if (isProduct) {
                var zmk = fields.find(function (x) {
                  return x.code && x.code === 'UF_CRM_33_ZMK';
                });
                if (!!zmk) {
                  fields = fields.filter(function (x) {
                    return x.code && x.code !== 'UF_CRM_33_ZMK';
                  });
                }
              }
              fields = fields.filter(function (x) {
                return x.code && x.code !== 'UF_CRM_33_VID' && x.code !== 'UF_IS_PRODUCT';
              });
              receiver.setDataKey('fieldChanged', true);
              receiver.setDataKey('fields', fields);
              if (!!vid) {
                if (!receiver.container.querySelector('.crm-kanban-item-fields-item-vid')) {
                  var params = {
                    'text': BX.Type.isArray(vid.value) ? vid.value.join(', ') : vid.value
                  };
                  var nodes = [BX.create('div', {
                    props: {
                      className: 'crm-kanban-item-fields-item-title-text'
                    },
                    html: vid.title
                  })];
                  var vidClassName = isProduct ? "crm-kanban-item-fields-item-vid--product" : "crm-kanban-item-fields-item-vid--supply";
                  var vidField = BX.create("div", {
                    // style: {
                    // 	'border-color': receiver.data.columnColor,
                    // },
                    props: {
                      className: "crm-kanban-item-fields-item crm-kanban-item-fields-item-vid " + vidClassName
                    },
                    children: [BX.create("div", params)]
                  });
                  receiver.container.prepend(vidField);
                }
              }
              if (isProduct) receiver.container.classList.add("crm-kanban-item--product");else receiver.container.classList.add("crm-kanban-item--supply");
            }
          }
          return parentFunction;
        }
      });
      BX.CRM.Kanban.Item.prototype = proxy;
    }

    function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
    function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
    var namespace$1 = main_core.Reflection.namespace('BX.Crm');
    var SupplyListFilters = /*#__PURE__*/function () {
      function SupplyListFilters(options) {
        babelHelpers.classCallCheck(this, SupplyListFilters);
        babelHelpers.defineProperty(this, "options", {});
        babelHelpers.defineProperty(this, "itemsList", []);
        babelHelpers.defineProperty(this, "mainFilter", null);
        if (!!document.querySelector('.supply-filter-panel')) return;
        this.prepareOptions(options);
        this.handleEvents();
        this.drawFilters();
      }

      /**
       * Подготовка настроек
       * @param options
       */
      babelHelpers.createClass(SupplyListFilters, [{
        key: "prepareOptions",
        value: function prepareOptions(options) {
          this.options = options;
        }
        /**
         * Добавление элемента после элемента
         * @param newNode
         * @param referenceNode
         */
      }, {
        key: "insertAfter",
        value: function insertAfter(newNode, referenceNode) {
          referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
        }
        /**
         * Обработка событий
         */
      }, {
        key: "handleEvents",
        value: function handleEvents() {
          var _this = this;
          BX.Event.EventEmitter.subscribe('BX.Main.Filter:beforeApply', function (event) {
            var filter = event.data[2];
            var filterValues = filter.getFilterFieldsValues();
            _this.updateFilterItems(filterValues);
          });
          BX.Event.EventEmitter.subscribe('BX.Filter.Search:beforeSquaresUpdate', function (event) {
            var filter = event.data[1].parent;
            _this.setFilter(filter);
            var filterValues = filter.getFilterFieldsValues();
            _this.updateFilterItems(filterValues);
          });
          BX.Event.EventEmitter.subscribe('SupplyFilterPanel.Item:activate', function (event) {
            var item = event.data;
            _this.itemsList.forEach(function (filterItem) {
              if (filterItem.group !== item.group) return;
              if (filterItem.code === item.code) return;
              if (!filterItem.active) return;
              _this.crossClick(null, filterItem, false);
            });
          });
        }
        /**
         * Обновление элементов фильтров после обновления Main.Filter
         * @param filterValues
         */
      }, {
        key: "updateFilterItems",
        value: function updateFilterItems(filterValues) {
          var _this2 = this;
          this.itemsList.forEach(function (filterItem) {
            var filterFieldCount = Object.entries(filterItem.fields).length;
            if (filterItem.code === 'only_my') filterFieldCount -= 1;
            var appliedFields = 0;
            Object.entries(filterItem.fields).forEach(function (entries) {
              var _filterValues$fieldKe;
              var _entries = babelHelpers.slicedToArray(entries, 2),
                fieldKey = _entries[0],
                fieldValue = _entries[1];
              var filterCurrentValue = (_filterValues$fieldKe = filterValues[fieldKey]) !== null && _filterValues$fieldKe !== void 0 ? _filterValues$fieldKe : null;
              if ("ASSIGNED_BY_ID_label" === fieldKey) return;
              if (BX.type.isArray(fieldValue)) {
                var areArraysEqual = fieldValue.length === filterCurrentValue.length && fieldValue.every(function (value, index) {
                  return value === filterCurrentValue[index];
                });
                if (areArraysEqual) {
                  appliedFields++;
                }
              } else if (BX.type.isPlainObject(fieldValue)) {
                if (BX.type.isObject(filterCurrentValue)) {
                  var filterValueLength = Object.values(filterCurrentValue).length;
                  if (filterValueLength === 1) {
                    if (filterCurrentValue[0] == fieldValue.VALUE) {
                      appliedFields++;
                    }
                  }
                }
              } else {
                if (fieldValue == filterCurrentValue) {
                  appliedFields++;
                }
              }
            });
            if (appliedFields === filterFieldCount) {
              _this2.setActive(filterItem);
            } else {
              _this2.setInactive(filterItem);
            }
          });
        }
        /**
         * Установка Main.Filter
         * @param filter
         */
      }, {
        key: "setFilter",
        value: function setFilter(filter) {
          this.mainFilter = filter;
        }
        /**
         * Отрисовка фильтров
         */
      }, {
        key: "drawFilters",
        value: function drawFilters() {
          var _this$options$filterF,
            _this3 = this;
          var switcher = document.querySelector(".crm-view-switcher");
          var previousNeighbor = switcher;
          (_this$options$filterF = this.options.filterFields) === null || _this$options$filterF === void 0 ? void 0 : _this$options$filterF.forEach(function (filterOption) {
            var filter = _this3.drawPanel(filterOption);
            _this3.insertAfter(filter, previousNeighbor);
            previousNeighbor = filter;
          });
        }
        /**
         * Отрисовка панелей фильтров
         * @param items
         * @returns {HTMLDivElement}
         */
      }, {
        key: "drawPanel",
        value: function drawPanel(items) {
          var html = "\n\t\t\t<div id=\"counter_panel_container\" class=\"crm-counter\">\n\t\t\t\t<div class=\"ui-counter-panel ui-counter-panel__scope\"></div>\n\t\t\t</div>\n\t\t";
          var panel = document.createElement("div");
          panel.classList.add("supply-filter-panel");
          panel.innerHTML = html;
          var counterPanel = panel.querySelector('.ui-counter-panel');
          var domItems = this.domItems(items);
          domItems.forEach(function (item) {
            counterPanel.append(item);
          });
          return panel;
        }
        /**
         * Отрисовка элементов панели фильтра
         * @param items
         * @returns {[]}
         */
      }, {
        key: "domItems",
        value: function domItems(items) {
          var _this4 = this;
          var domItems = [];
          items === null || items === void 0 ? void 0 : items.forEach(function (item) {
            var itemElement = document.createElement('div');
            itemElement.classList.add('ui-counter-panel__item');
            itemElement.innerHTML = "\n\t\t\t\t<div class=\"ui-counter-panel__item-title\">".concat(item.name, "</div>\n\t\t\t\t<div class=\"ui-counter-panel__item-cross\">\n\t\t\t\t\t<div class=\"ui-counter-panel__item-cross--icon\"></div>\n\t\t\t\t</div>\n\t\t\t");
            itemElement.addEventListener('click', function (e) {
              if (item.active) {
                _this4.crossClick(e, item);
              } else {
                _this4.itemClick(item);
              }
            });
            item.element = itemElement;
            domItems.push(itemElement);
            _this4.itemsList.push(item);
          });
          return domItems;
        }
        /**
         * Получить элемент фильтра по его коду
         * @param code
         * @returns {null}
         */
      }, {
        key: "getFilterItemByCode",
        value: function getFilterItemByCode(code) {
          var item = null;
          this.itemsList.forEach(function (filterItem) {
            if (filterItem.code != code) return;
            item = filterItem;
          });
          return item;
        }
        /**
         * Событие на клик на элемент фильтра (активация фильтра)
         * @param item
         */
      }, {
        key: "itemClick",
        value: function itemClick(item) {
          var api = this.mainFilter.getApi();
          this.setActive(item);
          BX.Event.EventEmitter.emit('SupplyFilterPanel.Item:activate', item);
          api.setFields(this.collectFilter());
          api.apply({});
        }
        /**
         * Установка активности элемента фильтра
         * @param item
         */
      }, {
        key: "setActive",
        value: function setActive(item) {
          item.active = true;
          item.element.classList.add('--active');
        }
        /**
         * Событие на клик на элемент фильтра (деактивация фильтра)
         * @param event
         * @param item
         */
      }, {
        key: "crossClick",
        value: function crossClick(event, item) {
          var applyFilter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
          var api = this.mainFilter.getApi();
          this.setInactive(item);
          if (applyFilter) {
            api.setFields(this.collectFilter());
            api.apply({});
            BX.Event.EventEmitter.emit('SupplyFilterPanel.Item:deactivate', item);
          }
        }
        /**
         * Установка неактивности элемента фильтра
         * @param item
         */
      }, {
        key: "setInactive",
        value: function setInactive(item) {
          item.active = false;
          if (item.element.classList.contains('--active')) item.element.classList.remove('--active');
        }
        /**
         * Сбор активных фильтров
         * @returns {{}}
         */
      }, {
        key: "collectFilter",
        value: function collectFilter() {
          var filterFields = {};
          this.itemsList.forEach(function (filterItem) {
            if (!filterItem.active) return;
            filterFields = _objectSpread(_objectSpread({}, filterFields), filterItem.fields);
          });
          return filterFields;
        }
      }]);
      return SupplyListFilters;
    }();
    namespace$1.SupplyListFilters = SupplyListFilters;

    exports.SupplyListFilters = SupplyListFilters;

}((this.BX.Simple = this.BX.Simple || {}),BX));
