/// <reference path="../pb_data/types.d.ts" />
migrate((app) => {
  const collection = app.findCollectionByNameOrId("_pb_users_auth_")

  // add field
  collection.fields.addAt(10, new Field({
    "hidden": false,
    "id": "select3713686397",
    "maxSelect": 0,
    "name": "plan",
    "presentable": false,
    "required": false,
    "system": false,
    "type": "select",
    "values": [
      "free",
      "pro",
      "enterprise"
    ]
  }))

  // add field
  collection.fields.addAt(11, new Field({
    "hidden": false,
    "id": "number1998954114",
    "max": null,
    "min": null,
    "name": "monthlyLimit",
    "onlyInt": false,
    "presentable": false,
    "required": false,
    "system": false,
    "type": "number"
  }))

  return app.save(collection)
}, (app) => {
  const collection = app.findCollectionByNameOrId("_pb_users_auth_")

  // remove field
  collection.fields.removeById("select3713686397")

  // remove field
  collection.fields.removeById("number1998954114")

  return app.save(collection)
})
