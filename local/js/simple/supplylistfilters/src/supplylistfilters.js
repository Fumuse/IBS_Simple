import {Type, Reflection, ajax as Ajax} from 'main.core';

import './supplylistfilters.css'

const namespace = Reflection.namespace('BX.Crm');

import './supplykanban'

export class SupplyListFilters {
	options = {};
	itemsList = [];
	mainFilter = null;

	constructor(options) {
		if (!!document.querySelector('.supply-filter-panel')) return;

		this.prepareOptions(options);
		this.handleEvents();
		this.drawFilters();
	}

	/**
	 * Подготовка настроек
	 * @param options
	 */
	prepareOptions(options)
	{
		this.options = options;
	}

	/**
	 * Добавление элемента после элемента
	 * @param newNode
	 * @param referenceNode
	 */
	insertAfter(newNode, referenceNode) {
		referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
	}

	/**
	 * Обработка событий
	 */
	handleEvents() {
		BX.Event.EventEmitter.subscribe('BX.Main.Filter:beforeApply', (event) => {
			let filter = event.data[2];
			let filterValues = filter.getFilterFieldsValues();
			this.updateFilterItems(filterValues);
		});
		BX.Event.EventEmitter.subscribe('BX.Filter.Search:beforeSquaresUpdate', (event) => {
			let filter = event.data[1].parent;
			this.setFilter(filter);

			let filterValues = filter.getFilterFieldsValues();
			this.updateFilterItems(filterValues);
		});
		BX.Event.EventEmitter.subscribe('SupplyFilterPanel.Item:activate', (event) => {
			let item = event.data;

			this.itemsList.forEach((filterItem) => {
				if (filterItem.group !== item.group) return;
				if (filterItem.code === item.code) return;
				if (!filterItem.active) return;

				this.crossClick(null, filterItem, false)
			})
		});
	}

	/**
	 * Обновление элементов фильтров после обновления Main.Filter
	 * @param filterValues
	 */
	updateFilterItems(filterValues) {
		this.itemsList.forEach((filterItem) => {
			let filterFieldCount = Object.entries(filterItem.fields).length;
			if (filterItem.code === 'only_my') filterFieldCount -= 1;
			let appliedFields = 0;

			Object.entries(filterItem.fields).forEach((entries) => {
				let [fieldKey, fieldValue] = entries;
				let filterCurrentValue = filterValues[fieldKey] ?? null;

				if ("ASSIGNED_BY_ID_label" === fieldKey) return;

				if (BX.type.isArray(fieldValue))
				{
					let areArraysEqual = fieldValue.length === filterCurrentValue.length &&
						fieldValue.every((value, index) => value === filterCurrentValue[index]);

					if (areArraysEqual)
					{
						appliedFields++;
					}
				}
				else if (BX.type.isPlainObject(fieldValue))
				{
					if (BX.type.isObject(filterCurrentValue))
					{
						let filterValueLength = Object.values(filterCurrentValue).length;
						if (filterValueLength === 1)
						{
							if (filterCurrentValue[0] == fieldValue.VALUE)
							{
								appliedFields++;
							}
						}
					}
				}
				else
				{
					if (fieldValue == filterCurrentValue)
					{
						appliedFields++;
					}
				}
			});

			if (appliedFields === filterFieldCount)
			{
				this.setActive(filterItem);
			}
			else {
				this.setInactive(filterItem);
			}
		})
	}

	/**
	 * Установка Main.Filter
	 * @param filter
	 */
	setFilter(filter)
	{
		this.mainFilter = filter;
	}

	/**
	 * Отрисовка фильтров
	 */
	drawFilters()
	{
		let switcher = document.querySelector(".crm-view-switcher");
		let previousNeighbor = switcher;

		this.options.filterFields?.forEach((filterOption) => {
			let filter = this.drawPanel(filterOption);
			this.insertAfter(filter, previousNeighbor);

			previousNeighbor = filter;
		});
	}

	/**
	 * Отрисовка панелей фильтров
	 * @param items
	 * @returns {HTMLDivElement}
	 */
	drawPanel(items) {
		let html = `
			<div id="counter_panel_container" class="crm-counter">
				<div class="ui-counter-panel ui-counter-panel__scope"></div>
			</div>
		`;

		let panel = document.createElement("div");
		panel.classList.add("supply-filter-panel");
		panel.innerHTML = html;
		let counterPanel = panel.querySelector('.ui-counter-panel');

		let domItems = this.domItems(items);
		domItems.forEach((item) => {
			counterPanel.append(item);
		})

		return panel;
	}

	/**
	 * Отрисовка элементов панели фильтра
	 * @param items
	 * @returns {[]}
	 */
	domItems(items) {
		let domItems = [];
		items?.forEach((item) => {
			let itemElement = document.createElement('div');
			itemElement.classList.add('ui-counter-panel__item');
			itemElement.innerHTML = `
				<div class="ui-counter-panel__item-title">${item.name}</div>
				<div class="ui-counter-panel__item-cross">
					<div class="ui-counter-panel__item-cross--icon"></div>
				</div>
			`;

			itemElement.addEventListener('click', (e) => {
				if (item.active) {
					this.crossClick(e, item);
				} else {
					this.itemClick(item);
				}
			});

			item.element = itemElement;

			domItems.push(itemElement);
			this.itemsList.push(item);
		});

		return domItems;
	}

	/**
	 * Получить элемент фильтра по его коду
	 * @param code
	 * @returns {null}
	 */
	getFilterItemByCode(code)
	{
		let item = null;
		this.itemsList.forEach((filterItem) => {
			if (filterItem.code != code) return;
			item = filterItem;
		});

		return item;
	}

	/**
	 * Событие на клик на элемент фильтра (активация фильтра)
	 * @param item
	 */
	itemClick(item) {
		let api = this.mainFilter.getApi();

		this.setActive(item);

		BX.Event.EventEmitter.emit('SupplyFilterPanel.Item:activate', item);

		api.setFields(this.collectFilter());
		api.apply({});
	}

	/**
	 * Установка активности элемента фильтра
	 * @param item
	 */
	setActive(item)
	{
		item.active = true;
		item.element.classList.add('--active');
	}

	/**
	 * Событие на клик на элемент фильтра (деактивация фильтра)
	 * @param event
	 * @param item
	 */
	crossClick(event, item, applyFilter = true) {
		let api = this.mainFilter.getApi();

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
	setInactive(item)
	{
		item.active = false;

		if (item.element.classList.contains('--active'))
			item.element.classList.remove('--active');
	}

	/**
	 * Сбор активных фильтров
	 * @returns {{}}
	 */
	collectFilter() {
		let filterFields = {};
		this.itemsList.forEach((filterItem) => {
			if (!filterItem.active) return;

			filterFields = {...filterFields, ...filterItem.fields};
		})

		return filterFields;
	}
}

namespace.SupplyListFilters = SupplyListFilters;