/// <reference path="../pb_data/types.d.ts" />
migrate((app) => {
  const collection = app.findCollectionByNameOrId("pbc_4104317920")

  // update field
  collection.fields.addAt(4, new Field({
    "hidden": false,
    "id": "number1721384071",
    "max": null,
    "min": 0,
    "name": "requestCount",
    "onlyInt": true,
    "presentable": false,
    "required": false,
    "system": false,
    "type": "number"
  }))

  // update field
  collection.fields.addAt(5, new Field({
    "hidden": false,
    "id": "number1565185601",
    "max": null,
    "min": 0,
    "name": "errorCount",
    "onlyInt": true,
    "presentable": false,
    "required": false,
    "system": false,
    "type": "number"
  }))

  // update field
  collection.fields.addAt(6, new Field({
    "hidden": false,
    "id": "number3399857224",
    "max": null,
    "min": 0,
    "name": "totalLatencyMs",
    "onlyInt": true,
    "presentable": false,
    "required": false,
    "system": false,
    "type": "number"
  }))

  return app.save(collection)
}, (app) => {
  const collection = app.findCollectionByNameOrId("pbc_4104317920")

  // update field
  collection.fields.addAt(4, new Field({
    "hidden": false,
    "id": "number1721384071",
    "max": null,
    "min": null,
    "name": "requestCount",
    "onlyInt": false,
    "presentable": false,
    "required": true,
    "system": false,
    "type": "number"
  }))

  // update field
  collection.fields.addAt(5, new Field({
    "hidden": false,
    "id": "number1565185601",
    "max": null,
    "min": null,
    "name": "errorCount",
    "onlyInt": false,
    "presentable": false,
    "required": true,
    "system": false,
    "type": "number"
  }))

  // update field
  collection.fields.addAt(6, new Field({
    "hidden": false,
    "id": "number3399857224",
    "max": null,
    "min": null,
    "name": "totalLatencyMs",
    "onlyInt": false,
    "presentable": false,
    "required": true,
    "system": false,
    "type": "number"
  }))

  return app.save(collection)
})
