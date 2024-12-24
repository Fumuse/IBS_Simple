import {Reflection} from 'main.core';

const namespace = Reflection.namespace('BX.CRM.Kanban');
if (Object.values(namespace).length > 0)
{
	const proxy = new Proxy(BX.CRM.Kanban.Item.prototype, {
		get: (target, prop, receiver) => {
			let parentFunction = target[prop];
			if (prop === 'layoutFields')
			{
				let data = receiver.getData();
				if (!data.fieldChanged)
				{
					let fields = data.fields;

					let vid = fields.find(x => x.code && x.code === 'UF_CRM_33_VID');
					let isProductField = fields.find(x => x.code && x.code === 'UF_IS_PRODUCT');
					let isProduct = isProductField && isProductField.value === "да";
					if (isProduct)
					{
						let zmk = fields.find(x => x.code && x.code === 'UF_CRM_33_ZMK');
						if (!!zmk)
						{
							fields = fields.filter(x => x.code && x.code !== 'UF_CRM_33_ZMK');
						}
					}

					fields = fields.filter(x => x.code && x.code !== 'UF_CRM_33_VID' && x.code !== 'UF_IS_PRODUCT');
					receiver.setDataKey('fieldChanged', true);
					receiver.setDataKey('fields', fields);

					if (!!vid)
					{
						if (!receiver.container.querySelector('.crm-kanban-item-fields-item-vid'))
						{
							let params = {
								'text': BX.Type.isArray(vid.value) ? vid.value.join(', ') : vid.value
							};
							let nodes = [
								BX.create(
									'div',
									{
										props: {
											className: 'crm-kanban-item-fields-item-title-text'
										},
										html: vid.title
									}
								)
							];
							let vidClassName = isProduct ? "crm-kanban-item-fields-item-vid--product" : "crm-kanban-item-fields-item-vid--supply";
							let vidField = BX.create("div", {
								// style: {
								// 	'border-color': receiver.data.columnColor,
								// },
								props: {
									className: "crm-kanban-item-fields-item crm-kanban-item-fields-item-vid " + vidClassName
								},
								children: [
									BX.create("div", params)
								]
							});

							receiver.container.prepend(vidField);
						}
					}

					if (isProduct)
						receiver.container.classList.add("crm-kanban-item--product");
					else
						receiver.container.classList.add("crm-kanban-item--supply");
				}
			}

			return parentFunction;
		}
	});

	BX.CRM.Kanban.Item.prototype = proxy;
}