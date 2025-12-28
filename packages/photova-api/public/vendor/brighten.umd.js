(function(f,R){typeof exports=="object"&&typeof module<"u"?R(exports):typeof define=="function"&&define.amd?define(["exports"],R):(f=typeof globalThis<"u"?globalThis:f||self,R(f.Brighten={}))})(this,function(f){"use strict";class R{constructor(){this.listeners=new Map}on(t,e){return this.listeners.has(t)||this.listeners.set(t,new Set),this.listeners.get(t).add(e),()=>this.off(t,e)}once(t,e){const i=r=>{this.off(t,i),e(r)};return this.on(t,i)}off(t,e){const i=this.listeners.get(t);i&&i.delete(e)}emit(t,e){const i=this.listeners.get(t);i&&i.forEach(r=>{try{r(e)}catch(s){console.error(`Error in event listener for ${String(t)}:`,s)}})}removeAllListeners(t){t?this.listeners.delete(t):this.listeners.clear()}listenerCount(t){var e;return((e=this.listeners.get(t))==null?void 0:e.size)??0}}class W extends R{constructor(t){super(),this.canvasSize={width:0,height:0},this.displaySize={width:0,height:0},this.zoom=1,this.pan={x:0,y:0},this.isRendering=!1,this.renderQueued=!1,this.container=t,this.devicePixelRatio=window.devicePixelRatio||1,this.mainCanvas=document.createElement("canvas"),this.mainCtx=this.mainCanvas.getContext("2d",{willReadFrequently:!0}),this.workCanvas=document.createElement("canvas"),this.workCtx=this.workCanvas.getContext("2d",{willReadFrequently:!0}),this.displayCanvas=document.createElement("canvas"),this.displayCanvas.style.display="block",this.displayCanvas.style.width="100%",this.displayCanvas.style.height="100%",this.displayCtx=this.displayCtx=this.displayCanvas.getContext("2d"),t.appendChild(this.displayCanvas),this.setupResizeObserver()}setupResizeObserver(){new ResizeObserver(e=>{for(const i of e){const{width:r,height:s}=i.contentRect;this.updateDisplaySize({width:r,height:s})}}).observe(this.container)}updateDisplaySize(t){this.displaySize=t,this.displayCanvas.width=t.width*this.devicePixelRatio,this.displayCanvas.height=t.height*this.devicePixelRatio,this.displayCanvas.style.width=`${t.width}px`,this.displayCanvas.style.height=`${t.height}px`,this.displayCtx.scale(this.devicePixelRatio,this.devicePixelRatio),this.canvasSize.width>0&&this.canvasSize.height>0&&this.fitToView(),this.queueRender()}setCanvasSize(t){this.canvasSize=t,this.mainCanvas.width=t.width,this.mainCanvas.height=t.height,this.workCanvas.width=t.width,this.workCanvas.height=t.height,this.fitToView()}getCanvasSize(){return{...this.canvasSize}}fitToView(){if(this.displaySize.width<=0||this.displaySize.height<=0||this.canvasSize.width<=0||this.canvasSize.height<=0)return;const t=40,e=this.displaySize.width-t*2,i=this.displaySize.height-t*2;if(e<=0||i<=0){this.zoom=.1;return}const r=e/this.canvasSize.width,s=i/this.canvasSize.height;this.zoom=Math.min(r,s,1),this.pan={x:(this.displaySize.width-this.canvasSize.width*this.zoom)/2,y:(this.displaySize.height-this.canvasSize.height*this.zoom)/2},this.emit("zoom:change",{zoom:this.zoom}),this.emit("pan:change",{pan:this.pan}),this.queueRender()}setZoom(t,e){const i=this.zoom;if(this.zoom=Math.max(.1,Math.min(10,t)),e){const r=this.zoom/i;this.pan.x=e.x-(e.x-this.pan.x)*r,this.pan.y=e.y-(e.y-this.pan.y)*r,this.emit("pan:change",{pan:this.pan})}this.emit("zoom:change",{zoom:this.zoom}),this.queueRender()}getZoom(){return this.zoom}setPan(t){this.pan={...t},this.emit("pan:change",{pan:this.pan}),this.queueRender()}getPan(){return{...this.pan}}screenToCanvas(t){const e=this.displayCanvas.getBoundingClientRect();return{x:(t.x-e.left-this.pan.x)/this.zoom,y:(t.y-e.top-this.pan.y)/this.zoom}}canvasToScreen(t){const e=this.displayCanvas.getBoundingClientRect();return{x:t.x*this.zoom+this.pan.x+e.left,y:t.y*this.zoom+this.pan.y+e.top}}queueRender(){this.renderQueued||(this.renderQueued=!0,requestAnimationFrame(()=>this.performRender()))}performRender(){if(this.renderQueued=!1,this.isRendering){this.queueRender();return}this.emit("render",void 0)}render(t){this.isRendering=!0,this.mainCtx.clearRect(0,0,this.canvasSize.width,this.canvasSize.height);for(const e of t)e.visible&&this.renderLayer(e);this.renderToDisplay(),this.isRendering=!1}renderLayer(t){const{transform:e,opacity:i,blendMode:r}=t;switch(this.mainCtx.save(),this.mainCtx.globalCompositeOperation=this.blendModeToComposite(r),this.mainCtx.globalAlpha=i,this.mainCtx.translate(e.x,e.y),this.mainCtx.rotate(e.rotation),this.mainCtx.scale(e.scaleX,e.scaleY),this.mainCtx.transform(1,e.skewY,e.skewX,1,0,0),t.type){case"image":this.renderImageLayer(t);break;case"text":this.renderTextLayer(t);break;case"shape":this.renderShapeLayer(t);break;case"drawing":this.renderDrawingLayer(t);break;case"sticker":this.renderStickerLayer(t);break}this.mainCtx.restore()}renderImageLayer(t){this.mainCtx.drawImage(t.source,0,0)}renderTextLayer(t){const e=this.mainCtx;e.font=`${t.fontStyle} ${t.fontWeight} ${t.fontSize}px ${t.fontFamily}`,e.textAlign=t.textAlign,e.textBaseline="top",t.shadow&&(e.shadowColor=t.shadow.color,e.shadowBlur=t.shadow.blur,e.shadowOffsetX=t.shadow.offsetX,e.shadowOffsetY=t.shadow.offsetY);const i=t.text.split(`
`),r=t.fontSize*t.lineHeight;for(let s=0;s<i.length;s++){const a=s*r;t.stroke&&(e.strokeStyle=t.stroke.color,e.lineWidth=t.stroke.width,e.strokeText(i[s],0,a)),e.fillStyle=t.color,e.fillText(i[s],0,a)}}renderShapeLayer(t){const e=this.mainCtx;switch(e.beginPath(),t.shapeType){case"rectangle":t.cornerRadius?this.roundRect(e,0,0,100,100,t.cornerRadius):e.rect(0,0,100,100);break;case"ellipse":e.ellipse(50,50,50,50,0,0,Math.PI*2);break;case"polygon":case"line":case"arrow":if(t.points&&t.points.length>0){e.moveTo(t.points[0].x,t.points[0].y);for(let i=1;i<t.points.length;i++)e.lineTo(t.points[i].x,t.points[i].y);t.shapeType==="polygon"&&e.closePath()}break}t.fill&&(e.fillStyle=t.fill,e.fill()),t.stroke&&(e.strokeStyle=t.stroke.color,e.lineWidth=t.stroke.width,t.stroke.dashArray&&e.setLineDash(t.stroke.dashArray),e.stroke())}roundRect(t,e,i,r,s,a){t.moveTo(e+a,i),t.lineTo(e+r-a,i),t.quadraticCurveTo(e+r,i,e+r,i+a),t.lineTo(e+r,i+s-a),t.quadraticCurveTo(e+r,i+s,e+r-a,i+s),t.lineTo(e+a,i+s),t.quadraticCurveTo(e,i+s,e,i+s-a),t.lineTo(e,i+a),t.quadraticCurveTo(e,i,e+a,i),t.closePath()}renderDrawingLayer(t){const e=this.mainCtx;for(const i of t.paths)if(!(i.points.length<2)){e.beginPath(),e.strokeStyle=i.color,e.lineWidth=i.width,e.lineCap="round",e.lineJoin="round",e.globalAlpha=i.opacity,e.moveTo(i.points[0].x,i.points[0].y);for(let r=1;r<i.points.length;r++)e.lineTo(i.points[r].x,i.points[r].y);e.stroke()}}renderStickerLayer(t){this.mainCtx.drawImage(t.source,0,0)}renderToDisplay(){this.displayCtx.clearRect(0,0,this.displaySize.width,this.displaySize.height),this.drawTransparencyPattern(),this.displayCtx.save(),this.displayCtx.translate(this.pan.x,this.pan.y),this.displayCtx.scale(this.zoom,this.zoom),this.displayCtx.drawImage(this.mainCanvas,0,0),this.displayCtx.restore()}drawTransparencyPattern(){const e="#ffffff",i="#cccccc",r=Math.floor(this.pan.x/10)*10,s=Math.floor(this.pan.y/10)*10,a=this.pan.x+this.canvasSize.width*this.zoom,n=this.pan.y+this.canvasSize.height*this.zoom;this.displayCtx.save(),this.displayCtx.beginPath(),this.displayCtx.rect(this.pan.x,this.pan.y,this.canvasSize.width*this.zoom,this.canvasSize.height*this.zoom),this.displayCtx.clip();for(let o=s;o<n;o+=10)for(let h=r;h<a;h+=10){const c=((h-r)/10+(o-s)/10)%2===0;this.displayCtx.fillStyle=c?e:i,this.displayCtx.fillRect(h,o,10,10)}this.displayCtx.restore()}blendModeToComposite(t){return{normal:"source-over",multiply:"multiply",screen:"screen",overlay:"overlay",darken:"darken",lighten:"lighten","color-dodge":"color-dodge","color-burn":"color-burn","hard-light":"hard-light","soft-light":"soft-light",difference:"difference",exclusion:"exclusion",hue:"hue",saturation:"saturation",color:"color",luminosity:"luminosity"}[t]||"source-over"}getMainCanvas(){return this.mainCanvas}getWorkCanvas(){return this.workCanvas}getDisplayCanvas(){return this.displayCanvas}async export(t="png",e=.92){return new Promise((i,r)=>{this.mainCanvas.toBlob(s=>{s?i(s):r(new Error("Failed to export canvas"))},`image/${t}`,e)})}exportDataURL(t="png",e=.92){return this.mainCanvas.toDataURL(`image/${t}`,e)}getImageData(t=0,e=0,i,r){return this.mainCtx.getImageData(t,e,i??this.canvasSize.width,r??this.canvasSize.height)}putImageData(t,e=0,i=0){this.mainCtx.putImageData(t,e,i),this.queueRender()}destroy(){this.removeAllListeners(),this.displayCanvas.remove()}}const J={x:0,y:0,scaleX:1,scaleY:1,rotation:0,skewX:0,skewY:0};class X extends R{constructor(){super(...arguments),this.layers=new Map,this.layerOrder=[],this.selectedIds=new Set,this.activeId=null}generateId(){return`layer-${Date.now()}-${Math.random().toString(36).substr(2,9)}`}createBaseLayer(t,e){return{id:this.generateId(),type:t,name:e||`${t.charAt(0).toUpperCase()+t.slice(1)} Layer`,visible:!0,locked:!1,opacity:1,blendMode:"normal",transform:{...J}}}addImageLayer(t,e={}){const i={...this.createBaseLayer("image",e.name),type:"image",source:t,originalSource:t instanceof HTMLImageElement?t:void 0,filters:[],...e};return this.layers.set(i.id,i),this.layerOrder.push(i.id),this.emit("layer:add",{layer:i}),i}addTextLayer(t,e={}){const i={...this.createBaseLayer("text",e.name),type:"text",text:t,fontFamily:"Arial",fontSize:24,fontWeight:"normal",fontStyle:"normal",color:"#000000",textAlign:"left",lineHeight:1.2,letterSpacing:0,...e};return this.layers.set(i.id,i),this.layerOrder.push(i.id),this.emit("layer:add",{layer:i}),i}addShapeLayer(t,e={}){const i={...this.createBaseLayer("shape",e.name),type:"shape",shapeType:t,fill:"#cccccc",...e};return this.layers.set(i.id,i),this.layerOrder.push(i.id),this.emit("layer:add",{layer:i}),i}addDrawingLayer(t={}){const e={...this.createBaseLayer("drawing",t.name),type:"drawing",paths:[],...t};return this.layers.set(e.id,e),this.layerOrder.push(e.id),this.emit("layer:add",{layer:e}),e}addStickerLayer(t,e={}){const i={...this.createBaseLayer("sticker",e.name),type:"sticker",source:t,...e};return this.layers.set(i.id,i),this.layerOrder.push(i.id),this.emit("layer:add",{layer:i}),i}addAdjustmentLayer(t,e,i={}){const r={...this.createBaseLayer("adjustment",i.name),type:"adjustment",adjustmentType:t,settings:e,...i};return this.layers.set(r.id,r),this.layerOrder.push(r.id),this.emit("layer:add",{layer:r}),r}getLayer(t){return this.layers.get(t)}getLayers(){return this.layerOrder.map(t=>this.layers.get(t))}updateLayer(t,e){const i=this.layers.get(t);if(!i)return;const r={...i,...e};this.layers.set(t,r),this.emit("layer:update",{layerId:t,changes:e})}removeLayer(t){this.layers.has(t)&&(this.layers.delete(t),this.layerOrder=this.layerOrder.filter(e=>e!==t),this.selectedIds.delete(t),this.activeId===t&&(this.activeId=this.layerOrder[this.layerOrder.length-1]||null),this.emit("layer:remove",{layerId:t}))}reorderLayers(t){const e=t.filter(i=>this.layers.has(i));e.length===this.layerOrder.length&&(this.layerOrder=e,this.emit("layer:reorder",{layerIds:e}))}moveLayerUp(t){const e=this.layerOrder.indexOf(t);e<this.layerOrder.length-1&&([this.layerOrder[e],this.layerOrder[e+1]]=[this.layerOrder[e+1],this.layerOrder[e]],this.emit("layer:reorder",{layerIds:[...this.layerOrder]}))}moveLayerDown(t){const e=this.layerOrder.indexOf(t);e>0&&([this.layerOrder[e],this.layerOrder[e-1]]=[this.layerOrder[e-1],this.layerOrder[e]],this.emit("layer:reorder",{layerIds:[...this.layerOrder]}))}selectLayer(t,e=!1){e||this.selectedIds.clear(),this.selectedIds.add(t),this.activeId=t,this.emit("layer:select",{layerIds:[...this.selectedIds]})}deselectLayer(t){this.selectedIds.delete(t),this.activeId===t&&(this.activeId=[...this.selectedIds][0]||null),this.emit("layer:select",{layerIds:[...this.selectedIds]})}clearSelection(){this.selectedIds.clear(),this.activeId=null,this.emit("layer:select",{layerIds:[]})}getSelectedLayers(){return[...this.selectedIds].map(t=>this.layers.get(t)).filter(Boolean)}getSelectedIds(){return[...this.selectedIds]}getActiveLayer(){return this.activeId&&this.layers.get(this.activeId)||null}getActiveId(){return this.activeId}duplicateLayer(t){const e=this.layers.get(t);if(!e)return null;const i=JSON.parse(JSON.stringify(e));i.id=this.generateId(),i.name=`${e.name} (Copy)`,this.layers.set(i.id,i);const r=this.layerOrder.indexOf(t);return this.layerOrder.splice(r+1,0,i.id),this.emit("layer:add",{layer:i}),i}clear(){this.layers.clear(),this.layerOrder=[],this.selectedIds.clear(),this.activeId=null}restoreFromLayers(t,e,i){this.layers.clear(),this.layerOrder=[];for(const r of t)this.layers.set(r.id,r),this.layerOrder.push(r.id);this.activeId=e,this.selectedIds=new Set(i)}}class Y extends R{constructor(t=50){super(),this.history=[],this.currentIndex=-1,this.isBatching=!1,this.batchedChanges=[],this.maxSteps=t}push(t,e){if(this.isBatching)return;this.currentIndex<this.history.length-1&&(this.history=this.history.slice(0,this.currentIndex+1));const i={id:this.generateId(),timestamp:Date.now(),action:t,state:this.serializeState(e)};this.history.push(i),this.history.length>this.maxSteps?this.history.shift():this.currentIndex++,this.emitChange()}undo(){if(!this.canUndo())return null;this.currentIndex--;const t=this.history[this.currentIndex];return this.emit("history:undo",{entry:t}),this.emitChange(),t}redo(){if(!this.canRedo())return null;this.currentIndex++;const t=this.history[this.currentIndex];return this.emit("history:redo",{entry:t}),this.emitChange(),t}canUndo(){return this.currentIndex>0}canRedo(){return this.currentIndex<this.history.length-1}getCurrentState(){return this.currentIndex<0||this.currentIndex>=this.history.length?null:this.history[this.currentIndex].state}startBatch(){this.isBatching=!0,this.batchedChanges=[]}endBatch(t,e){this.isBatching=!1,this.batchedChanges=[],this.push(t,e)}cancelBatch(){this.isBatching=!1,this.batchedChanges=[]}clear(){this.history=[],this.currentIndex=-1,this.emitChange()}getHistory(){return[...this.history]}generateId(){return`${Date.now()}-${Math.random().toString(36).substr(2,9)}`}serializeState(t){const e=t.layers.map(i=>this.serializeLayer(i));return{...t,layers:e}}serializeLayer(t){if(t.type==="image"){const e=t,i={...t,sourceDataUrl:this.elementToDataUrl(e.source)};return e.originalSource&&(i.originalSourceDataUrl=this.elementToDataUrl(e.originalSource)),delete i.source,delete i.originalSource,i}if(t.type==="sticker"){const e=t,i={...t,sourceDataUrl:this.elementToDataUrl(e.source)};return delete i.source,i}return{...t}}elementToDataUrl(t){if(t instanceof HTMLCanvasElement)return t.toDataURL("image/png");const e=document.createElement("canvas");return e.width=t.naturalWidth||t.width,e.height=t.naturalHeight||t.height,e.getContext("2d").drawImage(t,0,0),e.toDataURL("image/png")}deserializeState(t){return this.deserializeStateAsync(t)}async deserializeStateAsync(t){const e=await Promise.all(t.layers.map(i=>this.deserializeLayer(i)));return{...t,layers:e}}async deserializeLayer(t){if(t.type==="image"&&t.sourceDataUrl){const e=await this.dataUrlToCanvas(t.sourceDataUrl);let i;t.originalSourceDataUrl&&(i=await this.dataUrlToImage(t.originalSourceDataUrl));const{sourceDataUrl:r,originalSourceDataUrl:s,...a}=t;return{...a,type:"image",source:e,originalSource:i,filters:t.filters||[]}}if(t.type==="sticker"&&t.sourceDataUrl){const e=await this.dataUrlToImage(t.sourceDataUrl),{sourceDataUrl:i,...r}=t;return{...r,type:"sticker",source:e}}return t}dataUrlToCanvas(t){return new Promise(e=>{const i=new Image;i.onload=()=>{const r=document.createElement("canvas");r.width=i.width,r.height=i.height,r.getContext("2d").drawImage(i,0,0),e(r)},i.src=t})}dataUrlToImage(t){return new Promise(e=>{const i=new Image;i.onload=()=>e(i),i.src=t})}emitChange(){this.emit("history:change",{canUndo:this.canUndo(),canRedo:this.canRedo()})}}class _{async loadFromUrl(t){return new Promise((e,i)=>{const r=new Image;r.crossOrigin="anonymous",r.onload=()=>{e({element:r,size:{width:r.naturalWidth,height:r.naturalHeight},originalSrc:t})},r.onerror=()=>{i(new Error(`Failed to load image from URL: ${t}`))},r.src=t})}async loadFromFile(t){return new Promise((e,i)=>{if(!t.type.startsWith("image/")){i(new Error("File is not an image"));return}const r=new FileReader;r.onload=async s=>{var a;try{const n=(a=s.target)==null?void 0:a.result,o=await this.loadFromUrl(n);e({...o,originalSrc:t.name})}catch(n){i(n)}},r.onerror=()=>{i(new Error("Failed to read file"))},r.readAsDataURL(t)})}async loadFromBlob(t){const e=URL.createObjectURL(t);try{return await this.loadFromUrl(e)}finally{URL.revokeObjectURL(e)}}async loadFromCanvas(t){return new Promise((e,i)=>{t.toBlob(async r=>{if(!r){i(new Error("Failed to convert canvas to blob"));return}try{const s=await this.loadFromBlob(r);e(s)}catch(s){i(s)}})})}async loadFromImageData(t){const e=document.createElement("canvas");return e.width=t.width,e.height=t.height,e.getContext("2d").putImageData(t,0,0),this.loadFromCanvas(e)}createCanvasFromImage(t){const e=document.createElement("canvas");return e.width=t.naturalWidth,e.height=t.naturalHeight,e.getContext("2d").drawImage(t,0,0),e}resizeImage(t,e,i){const r=t instanceof HTMLImageElement?t.naturalWidth:t.width,s=t instanceof HTMLImageElement?t.naturalHeight:t.height;let a=r,n=s;a>e&&(n=n*e/a,a=e),n>i&&(a=a*i/n,n=i);const o=document.createElement("canvas");o.width=Math.round(a),o.height=Math.round(n);const h=o.getContext("2d");return h.imageSmoothingEnabled=!0,h.imageSmoothingQuality="high",h.drawImage(t,0,0,o.width,o.height),o}}class O extends R{constructor(t){super(),this.activeTool="select",this.isDirty=!1,this.originalImageSize={width:0,height:0},this.config=t,this.container=this.resolveContainer(t.container),this.canvasManager=new W(this.container),this.layerManager=new X,this.historyManager=new Y(t.maxHistorySteps),this.imageLoader=new _,this.setupEventForwarding(),this.initialize()}resolveContainer(t){if(typeof t=="string"){const e=document.querySelector(t);if(!e)throw new Error(`Container not found: ${t}`);return e}return t}setupEventForwarding(){this.canvasManager.on("zoom:change",t=>this.emit("zoom:change",t)),this.canvasManager.on("pan:change",t=>this.emit("pan:change",t)),this.canvasManager.on("render",()=>{this.canvasManager.render(this.layerManager.getLayers()),this.emit("render",void 0)}),this.layerManager.on("layer:add",t=>{this.emit("layer:add",t),this.markDirty(),this.requestRender()}),this.layerManager.on("layer:remove",t=>{this.emit("layer:remove",t),this.markDirty(),this.requestRender()}),this.layerManager.on("layer:update",t=>{this.emit("layer:update",t),this.markDirty(),this.requestRender()}),this.layerManager.on("layer:select",t=>this.emit("layer:select",t)),this.layerManager.on("layer:reorder",t=>{this.emit("layer:reorder",t),this.requestRender()}),this.historyManager.on("history:undo",async t=>{await this.restoreState(t.entry.state),this.emit("history:undo",t)}),this.historyManager.on("history:redo",async t=>{await this.restoreState(t.entry.state),this.emit("history:redo",t)}),this.historyManager.on("history:change",t=>this.emit("history:change",t))}async initialize(){this.config.image?await this.loadImage(this.config.image):this.config.width&&this.config.height&&this.setCanvasSize({width:this.config.width,height:this.config.height})}async loadImage(t){let e;typeof t=="string"?e=await this.imageLoader.loadFromUrl(t):t instanceof HTMLImageElement?e={element:t,size:{width:t.naturalWidth,height:t.naturalHeight},originalSrc:t.src}:e={element:t,size:{width:t.width,height:t.height},originalSrc:"canvas"},this.originalImageSize=e.size,this.canvasManager.setCanvasSize(e.size),this.layerManager.clear();const i=(e.element instanceof HTMLCanvasElement,e.element);this.layerManager.addImageLayer(i,{name:"Background"}),this.historyManager.clear(),this.saveToHistory("Load image"),this.isDirty=!1,this.emit("image:load",e.size),this.requestRender()}async loadFromFile(t){const e=await this.imageLoader.loadFromFile(t);await this.loadImage(e.element)}setCanvasSize(t){this.originalImageSize=t,this.canvasManager.setCanvasSize(t)}getCanvasSize(){return this.canvasManager.getCanvasSize()}setTool(t){const e=this.activeTool;this.activeTool=t,this.emit("tool:change",{tool:t,previousTool:e})}getTool(){return this.activeTool}getLayerManager(){return this.layerManager}getCanvasManager(){return this.canvasManager}getHistoryManager(){return this.historyManager}undo(){this.historyManager.undo()}redo(){this.historyManager.redo()}canUndo(){return this.historyManager.canUndo()}canRedo(){return this.historyManager.canRedo()}saveToHistory(t){this.historyManager.push(t,this.getState())}getState(){return{layers:this.layerManager.getLayers(),activeLayerId:this.layerManager.getActiveId(),selectedLayerIds:this.layerManager.getSelectedIds(),activeTool:this.activeTool,zoom:this.canvasManager.getZoom(),pan:this.canvasManager.getPan(),canvasSize:this.canvasManager.getCanvasSize(),originalImageSize:this.originalImageSize,isDirty:this.isDirty}}async restoreState(t){const e=await this.historyManager.deserializeState(t);this.layerManager.restoreFromLayers(e.layers,e.activeLayerId,e.selectedLayerIds),this.canvasManager.setCanvasSize(e.canvasSize),this.originalImageSize=e.originalImageSize,this.activeTool=e.activeTool,this.requestRender()}markDirty(){this.isDirty=!0}isDirtyState(){return this.isDirty}requestRender(){this.canvasManager.queueRender()}setZoom(t,e){this.canvasManager.setZoom(t,e)}getZoom(){return this.canvasManager.getZoom()}zoomIn(){this.setZoom(this.getZoom()*1.25)}zoomOut(){this.setZoom(this.getZoom()/1.25)}fitToView(){this.canvasManager.fitToView()}setPan(t){this.canvasManager.setPan(t)}getPan(){return this.canvasManager.getPan()}screenToCanvas(t){return this.canvasManager.screenToCanvas(t)}canvasToScreen(t){return this.canvasManager.canvasToScreen(t)}async export(t={}){const{format:e="png",quality:i=.92}=t,r=await this.canvasManager.export(e,i);return this.emit("image:export",{format:e,size:this.canvasManager.getCanvasSize()}),r}exportDataURL(t="png",e=.92){return this.canvasManager.exportDataURL(t,e)}getImageData(){return this.canvasManager.getImageData()}destroy(){this.canvasManager.destroy(),this.removeAllListeners()}}class N{constructor(){this.processors=new Map,this.presets=new Map,this.registerBuiltInFilters(),this.registerBuiltInPresets()}registerBuiltInFilters(){this.registerFilter({type:"brightness",apply:(t,e)=>{const i=t.data,r=e*255;for(let s=0;s<i.length;s+=4)i[s]=this.clamp(i[s]+r),i[s+1]=this.clamp(i[s+1]+r),i[s+2]=this.clamp(i[s+2]+r);return t}}),this.registerFilter({type:"contrast",apply:(t,e)=>{const i=t.data,r=259*(e*255+255)/(255*(259-e*255));for(let s=0;s<i.length;s+=4)i[s]=this.clamp(r*(i[s]-128)+128),i[s+1]=this.clamp(r*(i[s+1]-128)+128),i[s+2]=this.clamp(r*(i[s+2]-128)+128);return t}}),this.registerFilter({type:"saturation",apply:(t,e)=>{const i=t.data,r=e+1;for(let s=0;s<i.length;s+=4){const a=.2989*i[s]+.587*i[s+1]+.114*i[s+2];i[s]=this.clamp(a+r*(i[s]-a)),i[s+1]=this.clamp(a+r*(i[s+1]-a)),i[s+2]=this.clamp(a+r*(i[s+2]-a))}return t}}),this.registerFilter({type:"hue",apply:(t,e)=>{const i=t.data,r=e*360,s=Math.cos(r*Math.PI/180),a=Math.sin(r*Math.PI/180),n=[s+(1-s)/3,(1-s)/3-Math.sqrt(1/3)*a,(1-s)/3+Math.sqrt(1/3)*a,(1-s)/3+Math.sqrt(1/3)*a,s+(1-s)/3,(1-s)/3-Math.sqrt(1/3)*a,(1-s)/3-Math.sqrt(1/3)*a,(1-s)/3+Math.sqrt(1/3)*a,s+(1-s)/3];for(let o=0;o<i.length;o+=4){const h=i[o],c=i[o+1],l=i[o+2];i[o]=this.clamp(h*n[0]+c*n[1]+l*n[2]),i[o+1]=this.clamp(h*n[3]+c*n[4]+l*n[5]),i[o+2]=this.clamp(h*n[6]+c*n[7]+l*n[8])}return t}}),this.registerFilter({type:"exposure",apply:(t,e)=>{const i=t.data,r=Math.pow(2,e);for(let s=0;s<i.length;s+=4)i[s]=this.clamp(i[s]*r),i[s+1]=this.clamp(i[s+1]*r),i[s+2]=this.clamp(i[s+2]*r);return t}}),this.registerFilter({type:"temperature",apply:(t,e)=>{const i=t.data,r=e*30;for(let s=0;s<i.length;s+=4)i[s]=this.clamp(i[s]+r),i[s+2]=this.clamp(i[s+2]-r);return t}}),this.registerFilter({type:"tint",apply:(t,e)=>{const i=t.data,r=e*30;for(let s=0;s<i.length;s+=4)i[s+1]=this.clamp(i[s+1]+r);return t}}),this.registerFilter({type:"vibrance",apply:(t,e)=>{const i=t.data,r=e*2;for(let s=0;s<i.length;s+=4){const a=Math.max(i[s],i[s+1],i[s+2]),n=(i[s]+i[s+1]+i[s+2])/3,o=Math.abs(a-n)*2/255*r;i[s]=this.clamp(i[s]+(a-i[s])*o),i[s+1]=this.clamp(i[s+1]+(a-i[s+1])*o),i[s+2]=this.clamp(i[s+2]+(a-i[s+2])*o)}return t}}),this.registerFilter({type:"sharpen",apply:(t,e)=>{if(e===0)return t;const i=[0,-e,0,-e,1+4*e,-e,0,-e,0];return this.convolve(t,i)}}),this.registerFilter({type:"blur",apply:(t,e)=>{if(e===0)return t;const i=Math.ceil(e*10);return this.boxBlur(t,i)}}),this.registerFilter({type:"grayscale",apply:(t,e)=>{const i=t.data;for(let r=0;r<i.length;r+=4){const s=.2989*i[r]+.587*i[r+1]+.114*i[r+2],a=i[r]*(1-e)+s*e,n=i[r+1]*(1-e)+s*e,o=i[r+2]*(1-e)+s*e;i[r]=a,i[r+1]=n,i[r+2]=o}return t}}),this.registerFilter({type:"sepia",apply:(t,e)=>{const i=t.data;for(let r=0;r<i.length;r+=4){const s=i[r],a=i[r+1],n=i[r+2],o=.393*s+.769*a+.189*n,h=.349*s+.686*a+.168*n,c=.272*s+.534*a+.131*n;i[r]=this.clamp(s*(1-e)+o*e),i[r+1]=this.clamp(a*(1-e)+h*e),i[r+2]=this.clamp(n*(1-e)+c*e)}return t}}),this.registerFilter({type:"invert",apply:(t,e)=>{const i=t.data;for(let r=0;r<i.length;r+=4)i[r]=this.clamp(i[r]*(1-e)+(255-i[r])*e),i[r+1]=this.clamp(i[r+1]*(1-e)+(255-i[r+1])*e),i[r+2]=this.clamp(i[r+2]*(1-e)+(255-i[r+2])*e);return t}}),this.registerFilter({type:"vignette",apply:(t,e)=>{const i=t.data,r=t.width,s=t.height,a=r/2,n=s/2,o=Math.sqrt(a*a+n*n);for(let h=0;h<s;h++)for(let c=0;c<r;c++){const l=(h*r+c)*4,d=1-Math.sqrt((c-a)**2+(h-n)**2)/o*e;i[l]=this.clamp(i[l]*d),i[l+1]=this.clamp(i[l+1]*d),i[l+2]=this.clamp(i[l+2]*d)}return t}}),this.registerFilter({type:"noise",apply:(t,e)=>{const i=t.data,r=e*50;for(let s=0;s<i.length;s+=4){const a=(Math.random()-.5)*r;i[s]=this.clamp(i[s]+a),i[s+1]=this.clamp(i[s+1]+a),i[s+2]=this.clamp(i[s+2]+a)}return t}}),this.registerFilter({type:"grain",apply:(t,e)=>{const i=t.data,r=e*30;for(let s=0;s<i.length;s+=4){const a=(Math.random()-.5)*r,n=.2989*i[s]+.587*i[s+1]+.114*i[s+2],o=a*(1-n/255);i[s]=this.clamp(i[s]+o),i[s+1]=this.clamp(i[s+1]+o),i[s+2]=this.clamp(i[s+2]+o)}return t}})}registerBuiltInPresets(){this.registerPreset({id:"vintage",name:"Vintage",category:"Classic",filters:[{type:"saturation",value:-.3,enabled:!0},{type:"sepia",value:.4,enabled:!0},{type:"vignette",value:.3,enabled:!0},{type:"grain",value:.2,enabled:!0}]}),this.registerPreset({id:"noir",name:"Noir",category:"Classic",filters:[{type:"grayscale",value:1,enabled:!0},{type:"contrast",value:.3,enabled:!0},{type:"vignette",value:.4,enabled:!0}]}),this.registerPreset({id:"warm",name:"Warm",category:"Color",filters:[{type:"temperature",value:.3,enabled:!0},{type:"saturation",value:.1,enabled:!0}]}),this.registerPreset({id:"cool",name:"Cool",category:"Color",filters:[{type:"temperature",value:-.3,enabled:!0},{type:"saturation",value:.1,enabled:!0}]}),this.registerPreset({id:"vivid",name:"Vivid",category:"Color",filters:[{type:"saturation",value:.4,enabled:!0},{type:"vibrance",value:.3,enabled:!0},{type:"contrast",value:.1,enabled:!0}]}),this.registerPreset({id:"matte",name:"Matte",category:"Film",filters:[{type:"contrast",value:-.1,enabled:!0},{type:"brightness",value:.05,enabled:!0},{type:"saturation",value:-.1,enabled:!0}]}),this.registerPreset({id:"dramatic",name:"Dramatic",category:"Mood",filters:[{type:"contrast",value:.4,enabled:!0},{type:"saturation",value:-.2,enabled:!0},{type:"vignette",value:.5,enabled:!0}]}),this.registerPreset({id:"soft",name:"Soft",category:"Portrait",filters:[{type:"contrast",value:-.1,enabled:!0},{type:"brightness",value:.1,enabled:!0},{type:"blur",value:.05,enabled:!0}]})}registerFilter(t){this.processors.set(t.type,t)}registerPreset(t){this.presets.set(t.id,t)}applyFilter(t,e){if(!e.enabled||e.value===0)return t;const i=this.processors.get(e.type);return i?i.apply(t,e.value):(console.warn(`Filter processor not found: ${e.type}`),t)}applyFilters(t,e){let i=t;for(const r of e)i=this.applyFilter(i,r);return i}applyPreset(t,e){const i=this.presets.get(e);return i?this.applyFilters(t,i.filters):(console.warn(`Preset not found: ${e}`),t)}getPresets(){return[...this.presets.values()]}getPresetsByCategory(){const t=new Map;for(const e of this.presets.values()){const i=t.get(e.category)||[];i.push(e),t.set(e.category,i)}return t}clamp(t){return Math.max(0,Math.min(255,Math.round(t)))}convolve(t,e){const i=t.data,r=t.width,s=t.height,a=new Uint8ClampedArray(i.length),n=Math.sqrt(e.length),o=Math.floor(n/2);for(let h=0;h<s;h++)for(let c=0;c<r;c++){let l=0,g=0,d=0;for(let L=0;L<n;L++)for(let S=0;S<n;S++){const I=Math.min(r-1,Math.max(0,c+S-o)),y=(Math.min(s-1,Math.max(0,h+L-o))*r+I)*4,v=e[L*n+S];l+=i[y]*v,g+=i[y+1]*v,d+=i[y+2]*v}const u=(h*r+c)*4;a[u]=this.clamp(l),a[u+1]=this.clamp(g),a[u+2]=this.clamp(d),a[u+3]=i[u+3]}return new ImageData(a,r,s)}boxBlur(t,e){const i=e*2+1,r=1/(i*i),s=new Array(i*i).fill(r);return this.convolve(t,s)}}class B{constructor(){this.context=null,this.isActive=!1}attach(t){this.context=t,this.onAttach()}detach(){this.onDetach(),this.context=null}activate(){this.isActive=!0,this.onActivate()}deactivate(){this.isActive=!1,this.onDeactivate()}onAttach(){}onDetach(){}onActivate(){}onDeactivate(){}}class Q extends B{constructor(){super(...arguments),this.type="crop",this.name="Crop",this.cursor="crosshair",this.isDragging=!1,this.startPoint=null,this.cropRect=null,this.activeHandle=null}setAspectRatio(t){this.aspectRatio=t,this.cropRect&&t&&(this.cropRect.height=this.cropRect.width/t)}getCropRect(){return this.cropRect?{...this.cropRect}:null}setCropRect(t){this.cropRect={...t}}onActivate(){if(!this.context)return;const t=this.context.editor.getCanvasSize();this.cropRect={x:t.width*.1,y:t.height*.1,width:t.width*.8,height:t.height*.8}}onDeactivate(){this.cropRect=null,this.isDragging=!1,this.startPoint=null,this.activeHandle=null}onPointerDown(t,e){this.cropRect&&(this.activeHandle=this.getHandleAtPoint(t),this.isDragging=!0,this.startPoint=t)}onPointerMove(t,e){var s;if(!this.isDragging||!this.startPoint||!this.cropRect)return;const i=t.x-this.startPoint.x,r=t.y-this.startPoint.y;this.activeHandle?this.resizeCropRect(this.activeHandle,i,r):this.isPointInCropRect(this.startPoint)&&(this.cropRect.x+=i,this.cropRect.y+=r),this.startPoint=t,(s=this.context)==null||s.editor.requestRender()}onPointerUp(t,e){this.isDragging=!1,this.startPoint=null,this.activeHandle=null}apply(){if(!this.cropRect||!this.context)return null;const t={...this.cropRect},e=this.context.editor.getCanvasSize();return t.x=Math.max(0,Math.min(t.x,e.width-t.width)),t.y=Math.max(0,Math.min(t.y,e.height-t.height)),t.width=Math.min(t.width,e.width-t.x),t.height=Math.min(t.height,e.height-t.y),t}cancel(){this.cropRect=null}getHandleAtPoint(t){if(!this.cropRect)return null;const e=10,{x:i,y:r,width:s,height:a}=this.cropRect,n=[{name:"nw",x:i,y:r},{name:"n",x:i+s/2,y:r},{name:"ne",x:i+s,y:r},{name:"e",x:i+s,y:r+a/2},{name:"se",x:i+s,y:r+a},{name:"s",x:i+s/2,y:r+a},{name:"sw",x:i,y:r+a},{name:"w",x:i,y:r+a/2}];for(const o of n)if(Math.abs(t.x-o.x)<=e&&Math.abs(t.y-o.y)<=e)return o.name;return null}isPointInCropRect(t){if(!this.cropRect)return!1;const{x:e,y:i,width:r,height:s}=this.cropRect;return t.x>=e&&t.x<=e+r&&t.y>=i&&t.y<=i+s}resizeCropRect(t,e,i){if(!this.cropRect)return;const r=20;switch(t){case"nw":this.cropRect.x+=e,this.cropRect.y+=i,this.cropRect.width-=e,this.cropRect.height-=i;break;case"n":this.cropRect.y+=i,this.cropRect.height-=i;break;case"ne":this.cropRect.y+=i,this.cropRect.width+=e,this.cropRect.height-=i;break;case"e":this.cropRect.width+=e;break;case"se":this.cropRect.width+=e,this.cropRect.height+=i;break;case"s":this.cropRect.height+=i;break;case"sw":this.cropRect.x+=e,this.cropRect.width-=e,this.cropRect.height+=i;break;case"w":this.cropRect.x+=e,this.cropRect.width-=e;break}this.cropRect.width<r&&(this.cropRect.width=r),this.cropRect.height<r&&(this.cropRect.height=r),this.aspectRatio&&(t.includes("e")||t.includes("w")?this.cropRect.height=this.cropRect.width/this.aspectRatio:this.cropRect.width=this.cropRect.height*this.aspectRatio)}}class tt extends B{constructor(){super(...arguments),this.type="transform",this.name="Transform",this.cursor="move",this.isDragging=!1,this.startPoint=null,this.activeHandle=null,this.initialTransform=null,this.targetLayerId=null}onActivate(){var e;const t=(e=this.context)==null?void 0:e.editor.getLayerManager().getActiveLayer();t&&(this.targetLayerId=t.id,this.initialTransform={...t.transform})}onDeactivate(){this.isDragging=!1,this.startPoint=null,this.activeHandle=null,this.initialTransform=null,this.targetLayerId=null}onPointerDown(t,e){if(!this.context||!this.targetLayerId)return;this.activeHandle=this.getHandleAtPoint(t),this.activeHandle||(this.activeHandle="move"),this.isDragging=!0,this.startPoint=t;const i=this.context.editor.getLayerManager().getLayer(this.targetLayerId);i&&(this.initialTransform={...i.transform})}onPointerMove(t,e){if(!this.isDragging||!this.startPoint||!this.context||!this.targetLayerId)return;const i=t.x-this.startPoint.x,r=t.y-this.startPoint.y,s=this.context.editor.getLayerManager().getLayer(this.targetLayerId);if(!s||!this.initialTransform)return;const a={...s.transform};switch(this.activeHandle){case"move":a.x=this.initialTransform.x+i,a.y=this.initialTransform.y+r;break;case"rotate":const n=this.initialTransform.x,o=this.initialTransform.y,h=Math.atan2(this.startPoint.y-o,this.startPoint.x-n),c=Math.atan2(t.y-o,t.x-n);a.rotation=this.initialTransform.rotation+(c-h);break;case"se":case"nw":case"ne":case"sw":const l=1+i/100,g=1+r/100;if(e.shiftKey){const d=Math.max(l,g);a.scaleX=this.initialTransform.scaleX*d,a.scaleY=this.initialTransform.scaleY*d}else a.scaleX=this.initialTransform.scaleX*l,a.scaleY=this.initialTransform.scaleY*g;break;case"e":case"w":a.scaleX=this.initialTransform.scaleX*(1+i/100);break;case"n":case"s":a.scaleY=this.initialTransform.scaleY*(1+r/100);break}this.context.editor.getLayerManager().updateLayer(this.targetLayerId,{transform:a})}onPointerUp(t,e){this.isDragging&&this.context&&this.context.editor.saveToHistory("Transform"),this.isDragging=!1,this.startPoint=null,this.activeHandle=null}rotate(t){if(!this.context||!this.targetLayerId)return;const e=this.context.editor.getLayerManager().getLayer(this.targetLayerId);if(!e)return;const i=t*Math.PI/180;this.context.editor.getLayerManager().updateLayer(this.targetLayerId,{transform:{...e.transform,rotation:e.transform.rotation+i}}),this.context.editor.saveToHistory("Rotate")}flipHorizontal(){if(!this.context||!this.targetLayerId)return;const t=this.context.editor.getLayerManager().getLayer(this.targetLayerId);t&&(this.context.editor.getLayerManager().updateLayer(this.targetLayerId,{transform:{...t.transform,scaleX:t.transform.scaleX*-1}}),this.context.editor.saveToHistory("Flip Horizontal"))}flipVertical(){if(!this.context||!this.targetLayerId)return;const t=this.context.editor.getLayerManager().getLayer(this.targetLayerId);t&&(this.context.editor.getLayerManager().updateLayer(this.targetLayerId,{transform:{...t.transform,scaleY:t.transform.scaleY*-1}}),this.context.editor.saveToHistory("Flip Vertical"))}getHandleAtPoint(t){return null}}class V extends B{constructor(){super(...arguments),this.type="brush",this.name="Brush",this.cursor="crosshair",this.options={color:"#000000",size:10,opacity:1,hardness:1},this.isDrawing=!1,this.currentPath=[],this.drawingLayerId=null}setOptions(t){this.options={...this.options,...t}}getOptions(){return{...this.options}}onActivate(){this.ensureDrawingLayer()}ensureDrawingLayer(){if(!this.context)return;const t=this.context.editor.getLayerManager(),i=t.getLayers().find(r=>r.type==="drawing");if(i)this.drawingLayerId=i.id;else{const r=t.addDrawingLayer({name:"Drawing"});this.drawingLayerId=r.id}}onPointerDown(t,e){this.isDrawing=!0,this.currentPath=[t]}onPointerMove(t,e){this.isDrawing&&(this.currentPath.push(t),this.drawCurrentPath())}onPointerUp(t,e){this.isDrawing&&(this.isDrawing=!1,this.commitPath(),this.currentPath=[])}drawCurrentPath(){var t;(t=this.context)==null||t.editor.requestRender()}commitPath(){if(!this.context||!this.drawingLayerId||this.currentPath.length<2)return;const t=this.context.editor.getLayerManager(),e=t.getLayer(this.drawingLayerId);if(!e)return;const i={points:[...this.currentPath],color:this.options.color,width:this.options.size,opacity:this.options.opacity,tool:"brush"};t.updateLayer(this.drawingLayerId,{paths:[...e.paths,i]}),this.context.editor.saveToHistory("Draw")}}class F{constructor(t={}){this.options={timeout:3e4,...t}}async fetchWithTimeout(t,e={}){const i=new AbortController,r=setTimeout(()=>i.abort(),this.options.timeout);try{return await fetch(t,{...e,signal:i.signal,headers:{...this.options.headers,...e.headers}})}finally{clearTimeout(r)}}async blobToBase64(t){return new Promise((e,i)=>{const r=new FileReader;r.onloadend=()=>{const s=r.result;e(s.split(",")[1])},r.onerror=i,r.readAsDataURL(t)})}async base64ToBlob(t,e="image/png"){return(await fetch(`data:${e};base64,${t}`)).blob()}}class et extends F{constructor(t={}){super(t),this.baseUrl="https://api.remove.bg/v1.0",this.removeBgOptions=t}get name(){return"remove.bg"}async removeBackground(t){var s,a;if(!this.options.apiKey)throw new Error("API key is required for remove.bg");const e=new FormData;e.append("image_file",t),e.append("size",this.removeBgOptions.size||"auto"),e.append("type",this.removeBgOptions.type||"auto"),e.append("format",this.removeBgOptions.format||"png"),this.removeBgOptions.bgColor&&e.append("bg_color",this.removeBgOptions.bgColor);const i=await this.fetchWithTimeout(`${this.baseUrl}/removebg`,{method:"POST",headers:{"X-Api-Key":this.options.apiKey},body:e});if(!i.ok){const n=await i.json().catch(()=>({errors:[{title:"Unknown error"}]}));throw new Error(`remove.bg error: ${((a=(s=n.errors)==null?void 0:s[0])==null?void 0:a.title)||"Unknown error"}`)}return{foreground:await i.blob()}}async enhance(t,e){throw new Error("Enhance is not supported by remove.bg")}async upscale(t,e){throw new Error("Upscale is not supported by remove.bg")}async generativeFill(t,e,i){throw new Error("Generative fill is not supported by remove.bg")}}class it extends F{constructor(t={}){super(t),this.baseUrl="https://api.replicate.com/v1",this.models={backgroundRemoval:"cjwbw/rembg:fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003",enhance:"nightmareai/real-esrgan:42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b",upscale:"nightmareai/real-esrgan:42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b",generativeFill:"stability-ai/stable-diffusion-inpainting:c11bac58203367db93a3c552bd49a25a5418458ddffb7e90dae55780765e26d6"},this.pollingInterval=t.pollingInterval||1e3,this.maxPollingTime=t.maxPollingTime||6e4}get name(){return"Replicate"}async removeBackground(t){const e=await this.blobToBase64(t),i=await this.runModel(this.models.backgroundRemoval,{image:`data:image/png;base64,${e}`});return{foreground:await this.fetchImageAsBlob(i)}}async enhance(t,e){const i=await this.blobToBase64(t),r=await this.runModel(this.models.enhance,{image:`data:image/png;base64,${i}`,scale:e!=null&&e.strength?Math.min(4,Math.max(2,Math.round(e.strength*4))):2,face_enhance:!0});return{enhanced:await this.fetchImageAsBlob(r)}}async upscale(t,e){const i=await this.blobToBase64(t),r=Math.min(4,Math.max(2,Math.round(e))),s=await this.runModel(this.models.upscale,{image:`data:image/png;base64,${i}`,scale:r});return{upscaled:await this.fetchImageAsBlob(s),scale:r}}async generativeFill(t,e,i){const r=await this.blobToBase64(t),s=await this.blobToBase64(e),a=await this.runModel(this.models.generativeFill,{prompt:i,image:`data:image/png;base64,${r}`,mask:`data:image/png;base64,${s}`,num_outputs:1}),n=Array.isArray(a)?a[0]:a;return{filled:await this.fetchImageAsBlob(n)}}async runModel(t,e){if(!this.options.apiKey)throw new Error("API key is required for Replicate");const i=await this.fetchWithTimeout(`${this.baseUrl}/predictions`,{method:"POST",headers:{Authorization:`Token ${this.options.apiKey}`,"Content-Type":"application/json"},body:JSON.stringify({version:t.split(":")[1],input:e})});if(!i.ok){const s=await i.json().catch(()=>({detail:"Unknown error"}));throw new Error(`Replicate error: ${s.detail||"Unknown error"}`)}const r=await i.json();return this.pollForResult(r.id)}async pollForResult(t){const e=Date.now();for(;Date.now()-e<this.maxPollingTime;){const i=await this.fetchWithTimeout(`${this.baseUrl}/predictions/${t}`,{headers:{Authorization:`Token ${this.options.apiKey}`}});if(!i.ok)throw new Error("Failed to poll prediction status");const r=await i.json();if(r.status==="succeeded")return r.output;if(r.status==="failed")throw new Error(`Prediction failed: ${r.error||"Unknown error"}`);if(r.status==="canceled")throw new Error("Prediction was canceled");await new Promise(s=>setTimeout(s,this.pollingInterval))}throw new Error("Prediction timed out")}async fetchImageAsBlob(t){const e=await fetch(t);if(!e.ok)throw new Error("Failed to fetch image");return e.blob()}}class rt{constructor(){this.providers=new Map,this.defaultProvider=null}registerProvider(t,e){this.providers.set(t,e)}setDefaultProvider(t){this.defaultProvider=t}getProvider(t){return this.providers.get(t)||this.defaultProvider}isFeatureAvailable(t){return this.providers.has(t)||this.defaultProvider!==null}async removeBackground(t){const e=this.getProvider("backgroundRemoval");if(!e)throw new Error("No provider available for background removal");return e.removeBackground(t)}async enhance(t,e){const i=this.getProvider("enhance");if(!i)throw new Error("No provider available for image enhancement");return i.enhance(t,e)}async upscale(t,e){const i=this.getProvider("upscale");if(!i)throw new Error("No provider available for image upscaling");return i.upscale(t,e)}async generativeFill(t,e,i){const r=this.getProvider("generativeFill");if(!r)throw new Error("No provider available for generative fill");return r.generativeFill(t,e,i)}}class st{constructor(){this.plugins=new Map,this.hooks=new Map,this.editor=null}setEditor(t){this.editor=t}async register(t){const e=typeof t=="function"?t():t;if(this.plugins.has(e.name)){console.warn(`Plugin "${e.name}" is already registered`);return}this.editor&&await e.initialize({editor:this.editor}),this.plugins.set(e.name,e)}async unregister(t){const e=this.plugins.get(t);e&&(e.destroy&&await e.destroy(),this.plugins.delete(t))}getPlugin(t){return this.plugins.get(t)}getPlugins(){return[...this.plugins.values()]}addHook(t,e){return this.hooks.has(t)||this.hooks.set(t,new Set),this.hooks.get(t).add(e),()=>this.removeHook(t,e)}removeHook(t,e){const i=this.hooks.get(t);i&&i.delete(e)}async trigger(t,e){const i=this.hooks.get(t);if(i)for(const r of i)try{await r(e)}catch(s){console.error(`Error in hook "${t}":`,s)}}async destroyAll(){for(const t of this.plugins.values())t.destroy&&await t.destroy();this.plugins.clear(),this.hooks.clear()}}const at=`
.brighten-editor {
  --brighten-bg: #1a1a1a;
  --brighten-surface: #2d2d2d;
  --brighten-surface-hover: #3d3d3d;
  --brighten-border: #404040;
  --brighten-text: #ffffff;
  --brighten-text-secondary: #a0a0a0;
  --brighten-primary: #3b82f6;
  --brighten-primary-hover: #2563eb;
  --brighten-danger: #ef4444;
  --brighten-success: #22c55e;
  --brighten-radius: 6px;
  --brighten-transition: 150ms ease;

  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background: var(--brighten-bg);
  color: var(--brighten-text);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  overflow: hidden;
  user-select: none;
}

.brighten-editor.brighten-light {
  --brighten-bg: #f5f5f5;
  --brighten-surface: #ffffff;
  --brighten-surface-hover: #e5e5e5;
  --brighten-border: #d4d4d4;
  --brighten-text: #171717;
  --brighten-text-secondary: #525252;
}

.brighten-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background: var(--brighten-surface);
  border-bottom: 1px solid var(--brighten-border);
  min-height: 48px;
}

.brighten-header-left,
.brighten-header-center,
.brighten-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.brighten-header-center {
  flex: 1;
  justify-content: center;
}

.brighten-main {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.brighten-sidebar {
  display: flex;
  flex-direction: column;
  width: 64px;
  background: var(--brighten-surface);
  border-right: 1px solid var(--brighten-border);
}

.brighten-canvas-container {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: var(--brighten-bg);
}

.brighten-panel {
  width: 280px;
  background: var(--brighten-surface);
  border-left: 1px solid var(--brighten-border);
  overflow-y: auto;
}

.brighten-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--brighten-border);
  font-weight: 600;
}

.brighten-panel-content {
  padding: 16px;
}

.brighten-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  background: var(--brighten-surface);
  border: 1px solid var(--brighten-border);
  border-radius: var(--brighten-radius);
  color: var(--brighten-text);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-btn:hover {
  background: var(--brighten-surface-hover);
}

.brighten-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.brighten-btn-primary {
  background: var(--brighten-primary);
  border-color: var(--brighten-primary);
}

.brighten-btn-primary:hover {
  background: var(--brighten-primary-hover);
  border-color: var(--brighten-primary-hover);
}

.brighten-btn-icon {
  padding: 8px;
  min-width: 36px;
  min-height: 36px;
}

.brighten-btn svg {
  width: 16px;
  height: 16px;
  vertical-align: middle;
  flex-shrink: 0;
}

.brighten-tool-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 100%;
  padding: 12px 8px;
  background: transparent;
  border: none;
  color: var(--brighten-text-secondary);
  font-size: 10px;
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-tool-btn:hover {
  background: var(--brighten-surface-hover);
  color: var(--brighten-text);
}

.brighten-tool-btn.active {
  background: var(--brighten-primary);
  color: white;
}

.brighten-tool-btn svg {
  width: 24px;
  height: 24px;
}

.brighten-slider-group {
  margin-bottom: 16px;
}

.brighten-slider-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--brighten-text-secondary);
}

.brighten-slider {
  width: 100%;
  height: 4px;
  background: var(--brighten-border);
  border-radius: 2px;
  appearance: none;
  cursor: pointer;
}

.brighten-slider::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background: var(--brighten-primary);
  border-radius: 50%;
  cursor: pointer;
}

.brighten-presets-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.brighten-preset {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 8px;
  background: var(--brighten-bg);
  border: 2px solid transparent;
  border-radius: var(--brighten-radius);
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-preset:hover {
  border-color: var(--brighten-border);
}

.brighten-preset.active {
  border-color: var(--brighten-primary);
}

.brighten-preset-preview {
  width: 100%;
  aspect-ratio: 1;
  background: var(--brighten-surface);
  border-radius: 4px;
  overflow: hidden;
}

.brighten-preset-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.brighten-preset-name {
  font-size: 11px;
  color: var(--brighten-text-secondary);
}

.brighten-layers-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.brighten-layer-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--brighten-bg);
  border-radius: var(--brighten-radius);
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-layer-item:hover {
  background: var(--brighten-surface-hover);
}

.brighten-layer-item.active {
  background: var(--brighten-primary);
}

.brighten-layer-thumb {
  width: 32px;
  height: 32px;
  background: var(--brighten-surface);
  border-radius: 4px;
}

.brighten-layer-info {
  flex: 1;
  min-width: 0;
}

.brighten-layer-name {
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.brighten-layer-type {
  font-size: 11px;
  color: var(--brighten-text-secondary);
}

.brighten-zoom-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  background: var(--brighten-surface);
  border-radius: var(--brighten-radius);
}

.brighten-zoom-value {
  min-width: 48px;
  text-align: center;
  font-size: 12px;
  color: var(--brighten-text-secondary);
}

.brighten-toast {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 20px;
  background: var(--brighten-surface);
  border: 1px solid var(--brighten-border);
  border-radius: var(--brighten-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  z-index: 1000;
}

.brighten-modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.brighten-modal {
  background: var(--brighten-surface);
  border-radius: var(--brighten-radius);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  max-width: 90%;
  max-height: 90%;
  overflow: auto;
}

.brighten-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--brighten-border);
}

.brighten-modal-title {
  font-size: 16px;
  font-weight: 600;
}

.brighten-modal-body {
  padding: 20px;
}

.brighten-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 20px;
  border-top: 1px solid var(--brighten-border);
}

.brighten-crop-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.brighten-crop-mask {
  fill: rgba(0, 0, 0, 0.5);
}

.brighten-crop-area {
  stroke: white;
  stroke-width: 2;
  fill: none;
}

.brighten-crop-handle {
  fill: white;
  stroke: var(--brighten-primary);
  stroke-width: 2;
  cursor: pointer;
  pointer-events: auto;
}

.brighten-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  z-index: 100;
}

.brighten-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--brighten-border);
  border-top-color: var(--brighten-primary);
  border-radius: 50%;
  animation: brighten-spin 0.8s linear infinite;
}

@keyframes brighten-spin {
  to { transform: rotate(360deg); }
}

@keyframes brighten-ai-glow-rotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.brighten-ai-border {
  position: absolute;
  inset: 0;
  z-index: 200;
  pointer-events: none;
  border-radius: 4px;
  padding: 3px;
  background: conic-gradient(
    from var(--glow-angle, 0deg),
    #BC82F3,
    #F5B9EA,
    #8D9FFF,
    #AA6EEE,
    #FF6778,
    #FFBA71,
    #C686FF,
    #BC82F3
  );
  -webkit-mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: brighten-ai-border-rotate 2s linear infinite;
}

@keyframes brighten-ai-border-rotate {
  to { --glow-angle: 360deg; }
}

@property --glow-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}
`;function nt(){if(typeof document>"u"||document.getElementById("brighten-styles"))return;const b=document.createElement("style");b.id="brighten-styles",b.textContent=at,document.head.appendChild(b)}const p={crop:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M6 3v6M6 13v8M3 6h6M13 6h8M18 21v-6M18 11V3M21 18h-6M11 18H3"/>
  </svg>`,rotate:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12a9 9 0 11-9-9c2.52 0 4.85 1 6.6 2.6L21 8"/>
    <path d="M21 3v5h-5"/>
  </svg>`,transform:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="4" y="4" width="16" height="16" rx="2"/>
    <path d="M9 9h.01M15 9h.01M9 15h.01M15 15h.01"/>
  </svg>`,brush:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 00-2.82 0L8 7l9 9 1.59-1.59a2 2 0 000-2.82L17 10l4.37-4.37a2.12 2.12 0 10-3-3z"/>
    <path d="M9 8c-2 3-4 3.5-7 4l8 10c2-1 6-5 6-7"/>
  </svg>`,text:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 6V4h14v2M9 20h6M12 4v16"/>
  </svg>`,shapes:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/>
    <rect x="8" y="8" width="8" height="8" rx="1"/>
  </svg>`,filter:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="8" cy="8" r="5"/>
    <circle cx="16" cy="8" r="5"/>
    <circle cx="12" cy="14" r="5"/>
  </svg>`,adjust:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M8 4v4M8 12v8M16 4v8M16 16v4"/>
    <circle cx="8" cy="10" r="2"/>
    <circle cx="16" cy="14" r="2"/>
  </svg>`,layers:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 3L3 8l9 5 9-5-9-5z"/>
    <path d="M3 16l9 5 9-5"/>
    <path d="M3 12l9 5 9-5"/>
  </svg>`,undo:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 7v6h6"/>
    <path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/>
  </svg>`,redo:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 7v6h-6"/>
    <path d="M3 17a9 9 0 019-9 9 9 0 016 2.3l3 2.7"/>
  </svg>`,zoomIn:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="7"/>
    <path d="M21 21l-4-4M11 8v6M8 11h6"/>
  </svg>`,zoomOut:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="7"/>
    <path d="M21 21l-4-4M8 11h6"/>
  </svg>`,download:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
    <path d="M7 10l5 5 5-5M12 15V3"/>
  </svg>`,upload:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
    <path d="M17 8l-5-5-5 5M12 3v12"/>
  </svg>`,eye:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
    <circle cx="12" cy="12" r="3"/>
  </svg>`,eyeOff:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
    <path d="M2 2l20 20"/>
  </svg>`,trash:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/>
  </svg>`,copy:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="9" y="9" width="13" height="13" rx="2"/>
    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
  </svg>`,flipH:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 3v18M16 7l4 5-4 5M8 7L4 12l4 5"/>
  </svg>`,flipV:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 12h18M7 8L12 4l5 4M7 16l5 4 5-4"/>
  </svg>`,close:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18 6L6 18M6 6l12 12"/>
  </svg>`,check:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 6L9 17l-5-5"/>
  </svg>`,plus:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 5v14M5 12h14"/>
  </svg>`,minus:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 12h14"/>
  </svg>`,magic:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 3v2M12 19v2M5.64 5.64l1.41 1.41M16.95 16.95l1.41 1.41M3 12h2M19 12h2M5.64 18.36l1.41-1.41M16.95 7.05l1.41-1.41"/>
    <circle cx="12" cy="12" r="4"/>
  </svg>`,sparkles:`<svg viewBox="0 0 24 24" fill="currentColor">
    <path d="M9.5 2l1.5 4.5L15.5 8l-4.5 1.5L9.5 14l-1.5-4.5L3.5 8l4.5-1.5L9.5 2z"/>
    <path d="M18 12l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
    <path d="M6 16l.75 2.25L9 19l-2.25.75L6 22l-.75-2.25L3 19l2.25-.75L6 16z"/>
  </svg>`,image:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="18" height="18" rx="2"/>
    <circle cx="8.5" cy="8.5" r="1.5"/>
    <path d="M21 15l-5-5L5 21"/>
  </svg>`,select:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 3l14 9-6 2-2 6-6-17z"/>
    <path d="M14 14l5 5"/>
  </svg>`,focus:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="3"/>
    <path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>
  </svg>`,sticker:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/>
    <path d="M12 3c0 4.97 4.03 9 9 9"/>
  </svg>`,fill:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M19 11l-8-8-8.6 8.6a2 2 0 000 2.8l5.2 5.2a2 2 0 002.8 0L19 11z"/>
    <path d="M5 2l5 5"/>
    <path d="M2 13h15"/>
    <path d="M22 21a2 2 0 01-2-2c0-1.1.9-2 2-3 1.1 1 2 1.9 2 3a2 2 0 01-2 2z"/>
  </svg>`,redact:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4 4l16 16M4 8l16 16M4 12l16 16M4 16l12 12M4 20l8 8"/>
  </svg>`,palette:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="13.5" cy="6.5" r="1.5"/>
    <circle cx="17.5" cy="10.5" r="1.5"/>
    <circle cx="8.5" cy="7.5" r="1.5"/>
    <circle cx="6.5" cy="12.5" r="1.5"/>
    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.04-.23-.29-.38-.63-.38-1.04 0-.93.75-1.68 1.68-1.68H16c3.31 0 6-2.69 6-6 0-4.97-4.49-8.74-10-8.74z"/>
  </svg>`,eraser:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M7 21h10"/>
    <path d="M5.5 13.5L12 7l6.5 6.5a2.12 2.12 0 010 3l-3 3a2.12 2.12 0 01-3 0l-7-7a2.12 2.12 0 010-3z"/>
  </svg>`,expand:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
  </svg>`,save:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
    <path d="M17 21v-8H7v8M7 3v5h8"/>
  </svg>`,scan:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
    <circle cx="12" cy="12" r="3"/>
    <path d="M12 5v2M12 17v2M5 12h2M17 12h2"/>
  </svg>`},Z=["ai","select","crop","transform","filter","adjust","brush","text","shape","layers"];class U{constructor(t){this.currentPanel="ai",this.currentTool="ai",this.adjustments={},this.originalImageData=null,this.currentPreset=null,this.cropRect=null,this.cropOverlay=null,this.brushOptions={color:"#60a5fa",size:10,opacity:100},this.keyboardHandler=null,this.adjustmentHistoryTimeout=null,this.cropDragState={active:!1,handle:null,startX:0,startY:0,startRect:null},this.cropMouseMoveHandler=null,this.cropMouseUpHandler=null,this.filterPreviewCache=new Map,this.filterPreviewSource=null,this.panState={active:!1,startX:0,startY:0,startPanX:0,startPanY:0},this.panMouseMoveHandler=null,this.panMouseUpHandler=null,this.inpaintMode=!1,this.maskCanvas=null,this.maskCtx=null,this.maskOverlay=null,this.inpaintDrawState={drawing:!1,lastX:0,lastY:0},this.inpaintBrushSize=60,this.brushTool=null,this.brushMouseMoveHandler=null,this.brushMouseUpHandler=null,this.isSaving=!1,this.lastAnalysisCaption=null,this.aiGlowElement=null,this.config={theme:"dark",tools:Z,showHeader:!0,showSidebar:!0,showPanel:!0,...t},this.container=this.resolveContainer(t.container),this.filterEngine=new N,t.unstyled||nt(),this.root=this.createRoot(),this.applyCustomStyles(),this.container.appendChild(this.root);const e=this.root.querySelector(".brighten-canvas-container");this.editor=new O({container:e}),this.initializeBrushTool(e),this.setupEventListeners(),this.resetAdjustments(),this.showPanel("ai"),t.image&&this.loadImage(t.image)}resolveContainer(t){if(typeof t=="string"){const e=document.querySelector(t);if(!e)throw new Error(`Container not found: ${t}`);return e}return t}applyCustomStyles(){const t=this.config.styles;if(!t)return;const e={background:"--brighten-bg",surface:"--brighten-surface",surfaceHover:"--brighten-surface-hover",border:"--brighten-border",text:"--brighten-text",textSecondary:"--brighten-text-secondary",primary:"--brighten-primary",primaryHover:"--brighten-primary-hover",danger:"--brighten-danger",success:"--brighten-success",radius:"--brighten-radius",fontFamily:"font-family"};for(const[i,r]of Object.entries(t))r&&e[i]&&this.root.style.setProperty(e[i],r)}createRoot(){const t=document.createElement("div");return t.className=`brighten-editor ${this.config.theme==="light"?"brighten-light":""}`,t.innerHTML=`
      ${this.config.showHeader?this.renderHeader():""}
      <div class="brighten-main">
        ${this.config.showSidebar?this.renderSidebar():""}
        <div class="brighten-canvas-container"></div>
        ${this.config.showPanel?'<div class="brighten-panel"></div>':""}
      </div>
    `,t}renderHeader(){return`
      <header class="brighten-header">
        <div class="brighten-header-left">
          <button class="brighten-btn brighten-btn-icon" data-action="undo" title="Undo">
            ${p.undo}
          </button>
          <button class="brighten-btn brighten-btn-icon" data-action="redo" title="Redo">
            ${p.redo}
          </button>
        </div>
        <div class="brighten-header-center">
          <div class="brighten-zoom-controls">
            <button class="brighten-btn brighten-btn-icon" data-action="zoom-out" title="Zoom Out">
              ${p.zoomOut}
            </button>
            <span class="brighten-zoom-value">100%</span>
            <button class="brighten-btn brighten-btn-icon" data-action="zoom-in" title="Zoom In">
              ${p.zoomIn}
            </button>
          </div>
        </div>
        <div class="brighten-header-right">
          <button class="brighten-btn" data-action="open" title="Open Image">
            ${p.upload} Open
          </button>
          ${this.config.onSave?`<button class="brighten-btn" data-action="save" title="Save">
            ${p.save} Save
          </button>`:""}
          <button class="brighten-btn brighten-btn-primary" data-action="export" title="Export">
            ${p.download} Export
          </button>
          ${this.config.onClose?`<button class="brighten-btn brighten-btn-icon" data-action="close" title="Close">${p.close}</button>`:""}
        </div>
      </header>
    `}renderSidebar(){const t=[{type:"ai",icon:"sparkles",label:"AI",panel:"ai"},{type:"select",icon:"select",label:"Select"},{type:"crop",icon:"crop",label:"Crop",panel:"crop"},{type:"transform",icon:"transform",label:"Transform",panel:"transform"},{type:"filter",icon:"filter",label:"Filters",panel:"filters"},{type:"adjust",icon:"adjust",label:"Adjust",panel:"adjust"},{type:"brush",icon:"brush",label:"Brush",panel:"brush"},{type:"text",icon:"text",label:"Text",panel:"text"},{type:"shape",icon:"shapes",label:"Shapes",panel:"shapes"},{type:"layers",icon:"layers",label:"Layers",panel:"layers"}],e=this.config.tools||Z;return`
      <aside class="brighten-sidebar">
        ${t.filter(r=>e.includes(r.type)).map(r=>`
          <button class="brighten-tool-btn ${this.currentTool===r.type?"active":""}" 
                  data-tool="${r.type}" 
                  ${r.panel?`data-panel="${r.panel}"`:""}>
            ${p[r.icon]||""}
            <span>${r.label}</span>
          </button>
        `).join("")}
      </aside>
    `}renderFiltersPanel(){const t=this.filterEngine.getPresetsByCategory();this.generateFilterPreviews();const e=this.filterPreviewCache.get("none")||"";return`
      <div class="brighten-panel-header">
        <span>Filters</span>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-presets-grid" style="margin-bottom: 16px;">
          <button class="brighten-preset ${this.currentPreset===null?"active":""}" data-preset="none">
            <div class="brighten-preset-preview" style="background-image: url(${e}); background-size: cover; background-position: center;"></div>
            <span class="brighten-preset-name">None</span>
          </button>
        </div>
        ${Array.from(t.entries()).map(([i,r])=>`
          <div style="margin-bottom: 16px;">
            <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">${i}</div>
            <div class="brighten-presets-grid">
              ${r.map(s=>{const a=this.filterPreviewCache.get(s.id)||"";return`
                <button class="brighten-preset ${this.currentPreset===s.id?"active":""}" data-preset="${s.id}">
                  <div class="brighten-preset-preview" style="background-image: url(${a}); background-size: cover; background-position: center;"></div>
                  <span class="brighten-preset-name">${s.name}</span>
                </button>
              `}).join("")}
            </div>
          </div>
        `).join("")}
      </div>
    `}generateFilterPreviews(){const e=this.editor.getLayerManager().getLayers().find(d=>d.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=i instanceof HTMLImageElement?i.src:"canvas";if(this.filterPreviewSource===r&&this.filterPreviewCache.size>0)return;this.filterPreviewSource=r,this.filterPreviewCache.clear();const s=60,a=document.createElement("canvas"),n=i instanceof HTMLImageElement?i.naturalWidth:i.width,o=i instanceof HTMLImageElement?i.naturalHeight:i.height,h=Math.min(s/n,s/o);a.width=Math.round(n*h),a.height=Math.round(o*h);const c=a.getContext("2d");c.drawImage(i,0,0,a.width,a.height);const l=c.getImageData(0,0,a.width,a.height);this.filterPreviewCache.set("none",a.toDataURL("image/jpeg",.7));const g=this.filterEngine.getPresets();for(const d of g){const u=new ImageData(new Uint8ClampedArray(l.data),l.width,l.height),L=this.filterEngine.applyPreset(u,d.id);c.putImageData(L,0,0),this.filterPreviewCache.set(d.id,a.toDataURL("image/jpeg",.7))}}renderAdjustPanel(){return`
      <div class="brighten-panel-header">
        <span>Adjustments</span>
        <button class="brighten-btn" data-action="reset-adjustments" style="padding: 4px 8px; font-size: 12px;">Reset</button>
      </div>
      <div class="brighten-panel-content">
        ${[{id:"brightness",label:"Brightness",min:-100,max:100,default:0},{id:"contrast",label:"Contrast",min:-100,max:100,default:0},{id:"saturation",label:"Saturation",min:-100,max:100,default:0},{id:"exposure",label:"Exposure",min:-100,max:100,default:0},{id:"temperature",label:"Temperature",min:-100,max:100,default:0},{id:"tint",label:"Tint",min:-100,max:100,default:0},{id:"vibrance",label:"Vibrance",min:-100,max:100,default:0},{id:"sharpen",label:"Sharpen",min:0,max:100,default:0},{id:"vignette",label:"Vignette",min:0,max:100,default:0}].map(e=>`
          <div class="brighten-slider-group">
            <div class="brighten-slider-label">
              <span>${e.label}</span>
              <span data-value="${e.id}">${this.adjustments[e.id]??e.default}</span>
            </div>
            <input type="range" class="brighten-slider" 
                   data-adjust="${e.id}"
                   min="${e.min}" max="${e.max}" 
                   value="${this.adjustments[e.id]??e.default}">
          </div>
        `).join("")}
      </div>
    `}renderLayersPanel(){const t=this.editor.getLayerManager().getLayers(),e=this.editor.getLayerManager().getActiveId();return`
      <div class="brighten-panel-header">
        <span>Layers</span>
        <button class="brighten-btn brighten-btn-icon" data-action="add-layer" style="padding: 4px;">
          ${p.plus}
        </button>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-layers-list">
          ${t.slice().reverse().map(i=>`
            <div class="brighten-layer-item ${i.id===e?"active":""}" data-layer="${i.id}">
              <div class="brighten-layer-thumb"></div>
              <div class="brighten-layer-info">
                <div class="brighten-layer-name">${i.name}</div>
                <div class="brighten-layer-type">${i.type}</div>
              </div>
              <button class="brighten-btn brighten-btn-icon" data-action="toggle-visibility" data-layer="${i.id}" style="padding: 4px;">
                ${i.visible?p.eye:p.eyeOff}
              </button>
            </div>
          `).join("")}
        </div>
      </div>
    `}renderTextPanel(){return`
      <div class="brighten-panel-header">
        <span>Text</span>
      </div>
      <div class="brighten-panel-content">
        <button class="brighten-btn" data-action="add-text" style="width: 100%;">
          ${p.plus} Add Text
        </button>
        <div style="margin-top: 16px;">
          <div class="brighten-slider-group">
            <div class="brighten-slider-label">
              <span>Font Size</span>
              <span data-value="fontSize">24</span>
            </div>
            <input type="range" class="brighten-slider" data-text="fontSize" min="8" max="200" value="24">
          </div>
        </div>
      </div>
    `}renderShapesPanel(){return`
      <div class="brighten-panel-header">
        <span>Shapes</span>
      </div>
      <div class="brighten-panel-content">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
          ${[{type:"rectangle",label:"Rectangle"},{type:"ellipse",label:"Ellipse"},{type:"line",label:"Line"}].map(e=>`
            <button class="brighten-btn" data-action="add-shape" data-shape="${e.type}">
              ${e.label}
            </button>
          `).join("")}
        </div>
      </div>
    `}renderCropPanel(){return`
      <div class="brighten-panel-header">
        <span>Crop</span>
        <div style="display: flex; gap: 4px;">
          <button class="brighten-btn" data-action="cancel-crop" style="padding: 4px 8px; font-size: 12px;">Cancel</button>
          <button class="brighten-btn brighten-btn-primary" data-action="apply-crop" style="padding: 4px 8px; font-size: 12px;">Apply</button>
        </div>
      </div>
      <div class="brighten-panel-content">
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Aspect Ratio</div>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
            ${[{id:"free",label:"Free",ratio:null},{id:"square",label:"1:1",ratio:1},{id:"4:3",label:"4:3",ratio:1.3333333333333333},{id:"16:9",label:"16:9",ratio:1.7777777777777777},{id:"3:2",label:"3:2",ratio:1.5},{id:"9:16",label:"9:16",ratio:.5625}].map(e=>`
              <button class="brighten-btn" data-action="set-crop-ratio" data-ratio="${e.ratio??"free"}" style="padding: 8px; font-size: 12px;">
                ${e.label}
              </button>
            `).join("")}
          </div>
        </div>
        <div style="font-size: 12px; color: var(--brighten-text-secondary);">
          Drag the corners or edges to adjust the crop area.
        </div>
      </div>
    `}renderTransformPanel(){return`
      <div class="brighten-panel-header">
        <span>Transform</span>
      </div>
      <div class="brighten-panel-content">
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Rotate</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
            <button class="brighten-btn" data-action="rotate-ccw" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px; transform: scaleX(-1);">${p.rotate}</span>
              90° Left
            </button>
            <button class="brighten-btn" data-action="rotate-cw" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${p.rotate}</span>
              90° Right
            </button>
          </div>
        </div>
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Flip</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
            <button class="brighten-btn" data-action="flip-h" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${p.flipH}</span>
              Horizontal
            </button>
            <button class="brighten-btn" data-action="flip-v" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${p.flipV}</span>
              Vertical
            </button>
          </div>
        </div>
        <div style="font-size: 12px; color: var(--brighten-text-secondary);">
          Select a layer first, then use these controls to transform it.
        </div>
      </div>
    `}renderAIPanel(){const t=!!this.config.apiEndpoint,e="display: inline-block; width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;";return`
      <div class="brighten-panel-header">
        <span>AI Tools</span>
      </div>
      <div class="brighten-panel-content">
        ${t?`
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <button class="brighten-btn" data-action="analyze" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.scan}</span> Analyze Image
            </button>
            <button class="brighten-btn" data-action="remove-background" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.magic}</span> Remove Background
            </button>
            <button class="brighten-btn" data-action="unblur" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.focus}</span> Unblur / Enhance
            </button>
            <button class="brighten-btn" data-action="upscale" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.expand}</span> Upscale 4x
            </button>
            <button class="brighten-btn" data-action="colorize" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.palette}</span> Colorize
            </button>
            <button class="brighten-btn" data-action="restore" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.magic}</span> Restore Photo
            </button>
            <button class="brighten-btn" data-action="start-inpaint" style="width: 100%; justify-content: flex-start;">
              <span style="${e}">${p.eraser}</span> Remove Objects
            </button>
          </div>
          ${this.inpaintMode?`
          <div style="margin-top: 12px; padding: 12px; background: var(--brighten-bg); border-radius: 6px;">
            <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Paint over objects to remove</div>
            <div class="brighten-slider-group" style="margin-bottom: 12px;">
              <div class="brighten-slider-label">
                <span>Brush Size</span>
                <span data-value="inpaintBrush">${this.inpaintBrushSize}</span>
              </div>
              <input type="range" class="brighten-slider" data-inpaint="brushSize" min="5" max="100" value="${this.inpaintBrushSize}">
            </div>
            <div style="display: flex; gap: 8px;">
              <button class="brighten-btn" data-action="cancel-inpaint" style="flex: 1;">Cancel</button>
              <button class="brighten-btn brighten-btn-primary" data-action="apply-inpaint" style="flex: 1;">Remove</button>
            </div>
          </div>
          `:`
          <div style="margin-top: 12px; font-size: 12px; color: var(--brighten-text-secondary);">
            Use AI to enhance your images automatically.
          </div>
          `}
        `:`
          <div style="padding: 16px; background: var(--brighten-bg-tertiary); border-radius: 6px; text-align: center;">
            <div style="font-size: 14px; color: var(--brighten-text-secondary); margin-bottom: 8px;">
              AI features require an API endpoint
            </div>
            <div style="font-size: 12px; color: var(--brighten-text-secondary);">
              Configure <code>apiEndpoint</code> in EditorUI options to enable AI tools.
            </div>
          </div>
        `}
      </div>
    `}renderBrushPanel(){return`
      <div class="brighten-panel-header">
        <span>Brush</span>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Color</span>
          </div>
          <input type="color" data-brush="color" value="${this.brushOptions.color}" 
                 style="width: 100%; height: 36px; border: none; border-radius: 4px; cursor: pointer; background: transparent;">
        </div>
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Size</span>
            <span data-value="brushSize">${this.brushOptions.size}</span>
          </div>
          <input type="range" class="brighten-slider" data-brush="size" min="1" max="100" value="${this.brushOptions.size}">
        </div>
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Opacity</span>
            <span data-value="brushOpacity">${this.brushOptions.opacity}%</span>
          </div>
          <input type="range" class="brighten-slider" data-brush="opacity" min="1" max="100" value="${this.brushOptions.opacity}">
        </div>
        <div style="margin-top: 16px; padding: 12px; background: var(--brighten-bg-tertiary); border-radius: 6px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Preview</div>
          <div style="display: flex; align-items: center; justify-content: center; height: 60px;">
            <div style="width: ${Math.min(this.brushOptions.size,60)}px; height: ${Math.min(this.brushOptions.size,60)}px; 
                        background: ${this.brushOptions.color}; border-radius: 50%; 
                        opacity: ${this.brushOptions.opacity/100};"></div>
          </div>
        </div>
      </div>
    `}showPanel(t){this.currentPanel=t;const e=this.root.querySelector(".brighten-panel");if(e)switch(t){case"filters":e.innerHTML=this.renderFiltersPanel();break;case"adjust":e.innerHTML=this.renderAdjustPanel();break;case"layers":e.innerHTML=this.renderLayersPanel();break;case"text":e.innerHTML=this.renderTextPanel();break;case"shapes":e.innerHTML=this.renderShapesPanel();break;case"crop":e.innerHTML=this.renderCropPanel();break;case"transform":e.innerHTML=this.renderTransformPanel();break;case"brush":e.innerHTML=this.renderBrushPanel();break;case"ai":e.innerHTML=this.renderAIPanel();break;default:e.innerHTML=""}}setupEventListeners(){this.root.addEventListener("click",t=>this.handleClick(t)),this.root.addEventListener("input",t=>this.handleInput(t)),this.keyboardHandler=t=>this.handleKeyboard(t),document.addEventListener("keydown",this.keyboardHandler),this.editor.on("zoom:change",({zoom:t})=>{const e=this.root.querySelector(".brighten-zoom-value");e&&(e.textContent=`${Math.round(t*100)}%`)}),this.editor.on("history:change",({canUndo:t,canRedo:e})=>{const i=this.root.querySelector('[data-action="undo"]'),r=this.root.querySelector('[data-action="redo"]');i&&(i.disabled=!t),r&&(r.disabled=!e)}),this.editor.on("layer:add",()=>this.refreshLayersPanel()),this.editor.on("layer:remove",()=>this.refreshLayersPanel()),this.editor.on("layer:update",()=>this.refreshLayersPanel()),this.editor.on("layer:select",()=>this.refreshLayersPanel()),this.setupCanvasPanning()}setupCanvasPanning(){const t=this.root.querySelector(".brighten-canvas-container");t&&(t.addEventListener("mousedown",e=>{const i=e.button===1,r=e.button===0;if(!i&&!r||r&&["brush","crop"].includes(this.currentTool)||r&&this.inpaintMode||this.cropRect)return;const a=this.editor.getPan();this.panState={active:!0,startX:e.clientX,startY:e.clientY,startPanX:a.x,startPanY:a.y},t.style.cursor="grabbing",this.panMouseMoveHandler=n=>{if(!this.panState.active)return;const o=n.clientX-this.panState.startX,h=n.clientY-this.panState.startY;this.editor.setPan({x:this.panState.startPanX+o,y:this.panState.startPanY+h})},this.panMouseUpHandler=()=>{this.panState.active=!1,t.style.cursor="grab",this.panMouseMoveHandler&&document.removeEventListener("mousemove",this.panMouseMoveHandler),this.panMouseUpHandler&&document.removeEventListener("mouseup",this.panMouseUpHandler)},document.addEventListener("mousemove",this.panMouseMoveHandler),document.addEventListener("mouseup",this.panMouseUpHandler)}),t.style.cursor="grab",t.addEventListener("wheel",e=>{e.preventDefault();const i=e.deltaY>0?.9:1.1,r=this.editor.getZoom();this.editor.setZoom(r*i)},{passive:!1}),t.addEventListener("dblclick",e=>{const i=this.getCanvasPoint(e,t),r=this.findTextLayerAtPoint(i);r&&this.startTextEdit(r)}))}findTextLayerAtPoint(t){const e=this.editor.getLayerManager().getLayers(),i=document.createElement("canvas").getContext("2d");for(let r=e.length-1;r>=0;r--){const s=e[r];if(s.type!=="text"||!s.visible)continue;const a=s;i.font=`${a.fontStyle} ${a.fontWeight} ${a.fontSize}px ${a.fontFamily}`;const o=i.measureText(a.text).width,h=a.fontSize*a.lineHeight,c=a.transform,l=c.x,g=c.y;if(t.x>=l&&t.x<=l+o&&t.y>=g-h&&t.y<=g)return a}return null}startTextEdit(t){const e=this.root.querySelector(".brighten-canvas-container");if(!e)return;const i=this.editor.getZoom(),r=this.editor.getPan(),s=document.createElement("input");s.type="text",s.value=t.text,s.style.cssText=`
      position: absolute;
      left: ${t.transform.x*i+r.x}px;
      top: ${(t.transform.y-t.fontSize)*i+r.y}px;
      font-family: ${t.fontFamily};
      font-size: ${t.fontSize*i}px;
      font-weight: ${t.fontWeight};
      font-style: ${t.fontStyle};
      color: ${t.color};
      background: transparent;
      border: 1px dashed rgba(255,255,255,0.5);
      outline: none;
      padding: 2px 4px;
      min-width: 100px;
      z-index: 1000;
    `;const a=()=>{const n=s.value.trim()||t.text;n!==t.text&&(this.editor.getLayerManager().updateLayer(t.id,{text:n}),this.editor.saveToHistory("Edit text")),s.remove()};s.addEventListener("blur",a),s.addEventListener("keydown",n=>{n.key==="Enter"?(n.preventDefault(),a()):n.key==="Escape"&&(s.value=t.text,s.blur())}),e.appendChild(s),s.focus(),s.select()}initializeBrushTool(t){const e=t.querySelector("canvas");e&&(this.brushTool=new V,this.brushTool.attach({editor:this.editor,canvas:e}),this.brushTool.setOptions({color:this.brushOptions.color,size:this.brushOptions.size,opacity:this.brushOptions.opacity/100}),t.addEventListener("mousedown",i=>{if(i.button!==0||this.currentTool!=="brush"||!this.brushTool)return;const r=this.getCanvasPoint(i,t);this.brushTool.activate(),this.brushTool.onPointerDown(r,i),t.style.cursor="crosshair",this.brushMouseMoveHandler=s=>{if(!this.brushTool)return;const a=this.getCanvasPoint(s,t);this.brushTool.onPointerMove(a,s)},this.brushMouseUpHandler=()=>{this.brushTool&&(this.brushTool.onPointerUp({x:0,y:0},new PointerEvent("pointerup")),this.brushTool.deactivate(),t.style.cursor="crosshair",this.brushMouseMoveHandler&&document.removeEventListener("mousemove",this.brushMouseMoveHandler),this.brushMouseUpHandler&&document.removeEventListener("mouseup",this.brushMouseUpHandler))},document.addEventListener("mousemove",this.brushMouseMoveHandler),document.addEventListener("mouseup",this.brushMouseUpHandler)}))}getCanvasPoint(t,e){const i=e.getBoundingClientRect(),r=this.editor.getZoom(),s=this.editor.getPan(),a=t.clientX-i.left,n=t.clientY-i.top,o=(a-s.x)/r,h=(n-s.y)/r;return{x:o,y:h}}handleKeyboard(t){const e=t.target;if(e.tagName==="INPUT"||e.tagName==="TEXTAREA")return;const r=navigator.platform.toUpperCase().indexOf("MAC")>=0?t.metaKey:t.ctrlKey;if(r&&t.key==="z"&&!t.shiftKey)t.preventDefault(),this.editor.undo();else if(r&&t.key==="z"&&t.shiftKey)t.preventDefault(),this.editor.redo();else if(r&&t.key==="y")t.preventDefault(),this.editor.redo();else if(t.key==="Escape")t.preventDefault(),this.cropRect?this.cancelCrop():this.currentPanel&&(this.showPanel(null),this.setTool("select"));else if(t.key==="Delete"||t.key==="Backspace"){const s=this.editor.getLayerManager().getActiveLayer();s&&s.type!=="image"&&(t.preventDefault(),this.editor.getLayerManager().removeLayer(s.id),this.editor.saveToHistory("Delete layer"))}else t.key==="v"||t.key==="V"?(this.setTool("select"),this.showPanel(null)):t.key==="c"||t.key==="C"?(this.setTool("crop"),this.showPanel("crop"),this.startCrop()):t.key==="b"||t.key==="B"?(this.setTool("brush"),this.showPanel("brush")):t.key==="t"||t.key==="T"?(this.setTool("text"),this.showPanel("text")):r&&t.key==="="?(t.preventDefault(),this.editor.zoomIn()):r&&t.key==="-"?(t.preventDefault(),this.editor.zoomOut()):r&&t.key==="0"&&(t.preventDefault(),this.editor.fitToView())}handleClick(t){var l,g;const i=t.target.closest("button");if(!i)return;const r=i.dataset.action,s=i.dataset.tool,a=i.dataset.panel,n=i.dataset.preset,o=i.dataset.layer,h=i.dataset.shape,c=i.dataset.ratio;if(s&&(this.setTool(s),a&&(this.showPanel(a),a==="crop"&&this.startCrop())),r)switch(r){case"undo":this.editor.undo();break;case"redo":this.editor.redo();break;case"zoom-in":this.editor.zoomIn();break;case"zoom-out":this.editor.zoomOut();break;case"open":this.openFilePicker();break;case"export":this.exportImage();break;case"save":this.saveImage();break;case"close":(g=(l=this.config).onClose)==null||g.call(l);break;case"reset-adjustments":this.resetAdjustmentsAndApply();break;case"add-text":this.addText();break;case"add-layer":this.addLayer();break;case"add-shape":h&&this.addShape(h);break;case"toggle-visibility":o&&this.toggleLayerVisibility(o);break;case"apply-crop":this.applyCrop();break;case"cancel-crop":this.cancelCrop();break;case"set-crop-ratio":c&&this.setCropRatio(c==="free"?null:parseFloat(c));break;case"rotate-cw":this.rotateImage(90);break;case"rotate-ccw":this.rotateImage(-90);break;case"flip-h":this.flipImage("horizontal");break;case"flip-v":this.flipImage("vertical");break;case"remove-background":this.removeBackground();break;case"unblur":this.unblur();break;case"upscale":this.upscale();break;case"colorize":this.colorize();break;case"restore":this.restore();break;case"start-inpaint":this.startInpaintMode();break;case"cancel-inpaint":this.cancelInpaintMode();break;case"apply-inpaint":this.applyInpaint();break;case"analyze":this.analyze();break}n&&this.applyPreset(n),o&&!r&&this.editor.getLayerManager().selectLayer(o)}handleInput(t){const e=t.target,i=e.dataset.adjust,r=e.dataset.brush;if(i){const a=parseInt(e.value,10);this.adjustments[i]=a;const n=this.root.querySelector(`[data-value="${i}"]`);n&&(n.textContent=String(a)),this.applyAdjustments(),this.saveAdjustmentToHistoryDebounced()}if(r){if(r==="color")this.brushOptions.color=e.value;else if(r==="size"){this.brushOptions.size=parseInt(e.value,10);const a=this.root.querySelector('[data-value="brushSize"]');a&&(a.textContent=String(this.brushOptions.size))}else if(r==="opacity"){this.brushOptions.opacity=parseInt(e.value,10);const a=this.root.querySelector('[data-value="brushOpacity"]');a&&(a.textContent=`${this.brushOptions.opacity}%`)}this.updateBrushToolOptions(),this.refreshBrushPreview()}if(e.dataset.inpaint==="brushSize"){this.inpaintBrushSize=parseInt(e.value,10);const a=this.root.querySelector('[data-value="inpaintBrush"]');a&&(a.textContent=String(this.inpaintBrushSize))}}refreshBrushPreview(){this.showPanel("brush")}updateBrushToolOptions(){this.brushTool&&this.brushTool.setOptions({color:this.brushOptions.color,size:this.brushOptions.size,opacity:this.brushOptions.opacity/100})}setTool(t){this.currentTool==="crop"&&t!=="crop"&&(this.removeCropOverlay(),this.cropRect=null),this.currentTool=t,this.editor.setTool(t),this.root.querySelectorAll(".brighten-tool-btn").forEach(i=>{i.classList.toggle("active",i.getAttribute("data-tool")===t)});const e=this.root.querySelector(".brighten-canvas-container");e&&(e.style.cursor=t==="brush"?"crosshair":"grab")}resetAdjustments(){this.adjustments={brightness:0,contrast:0,saturation:0,exposure:0,temperature:0,tint:0,vibrance:0,sharpen:0,vignette:0}}applyAdjustments(){const e=this.editor.getLayerManager().getLayers().find(o=>o.type==="image");if(!e||e.type!=="image")return;if(!this.originalImageData){const o=document.createElement("canvas"),h=e.source;o.width=h instanceof HTMLImageElement?h.naturalWidth:h.width,o.height=h instanceof HTMLImageElement?h.naturalHeight:h.height;const c=o.getContext("2d");c.drawImage(h,0,0),this.originalImageData=c.getImageData(0,0,o.width,o.height)}const i=Object.entries(this.adjustments).filter(([o,h])=>h!==0).map(([o,h])=>({type:o,value:h/100,enabled:!0})),r=new ImageData(new Uint8ClampedArray(this.originalImageData.data),this.originalImageData.width,this.originalImageData.height);let s;i.length===0?s=r:s=this.filterEngine.applyFilters(r,i);const a=document.createElement("canvas");a.width=s.width,a.height=s.height,a.getContext("2d").putImageData(s,0,0),this.editor.getLayerManager().updateLayer(e.id,{source:a})}saveAdjustmentToHistoryDebounced(){this.adjustmentHistoryTimeout&&clearTimeout(this.adjustmentHistoryTimeout),this.adjustmentHistoryTimeout=setTimeout(()=>{this.editor.saveToHistory("Adjust image"),this.adjustmentHistoryTimeout=null},500)}resetAdjustmentsAndApply(){this.resetAdjustments(),this.applyAdjustments(),this.editor.saveToHistory("Reset adjustments"),this.showPanel("adjust")}applyPreset(t){const i=this.editor.getLayerManager().getLayers().find(l=>l.type==="image");if(!i||i.type!=="image")return;const r=document.createElement("canvas"),s=i.source;r.width=s instanceof HTMLImageElement?s.naturalWidth:s.width,r.height=s instanceof HTMLImageElement?s.naturalHeight:s.height;const a=r.getContext("2d");a.drawImage(s,0,0);const n=a.getImageData(0,0,r.width,r.height);if(t==="none"){this.currentPreset=null,this.showPanel("filters");return}this.currentPreset,this.currentPreset=t;const o=this.filterEngine.applyPreset(n,t),h=document.createElement("canvas");h.width=o.width,h.height=o.height,h.getContext("2d").putImageData(o,0,0),this.editor.getLayerManager().updateLayer(i.id,{source:h}),this.editor.saveToHistory(`Apply ${t} filter`),this.originalImageData=null,this.showPanel("filters")}refreshLayersPanel(){this.currentPanel==="layers"&&this.showPanel("layers")}startCrop(){const t=this.editor.getCanvasSize(),e=.1;this.cropRect={x:t.width*e,y:t.height*e,width:t.width*(1-e*2),height:t.height*(1-e*2)},this.renderCropOverlay()}renderCropOverlay(){if(this.removeCropOverlay(),!this.cropRect)return;const t=this.root.querySelector(".brighten-canvas-container");if(!t)return;const e=document.createElement("div");e.className="brighten-crop-overlay-container",e.style.cssText="position: absolute; inset: 0; pointer-events: none;",this.editor.getCanvasSize();const i=this.editor.getZoom(),r=this.editor.getPan(),s=(c,l)=>({x:c*i+r.x,y:l*i+r.y}),a=s(this.cropRect.x,this.cropRect.y),n=s(this.cropRect.x+this.cropRect.width,this.cropRect.y+this.cropRect.height),o=n.x-a.x,h=n.y-a.y;e.innerHTML=`
      <svg width="100%" height="100%" style="position: absolute; inset: 0;">
        <defs>
          <mask id="crop-mask">
            <rect width="100%" height="100%" fill="white"/>
            <rect x="${a.x}" y="${a.y}" width="${o}" height="${h}" fill="black"/>
          </mask>
        </defs>
        <rect width="100%" height="100%" fill="rgba(0,0,0,0.5)" mask="url(#crop-mask)"/>
        <rect x="${a.x}" y="${a.y}" width="${o}" height="${h}" 
              fill="none" stroke="white" stroke-width="2" stroke-dasharray="5,5"/>
        <rect x="${a.x}" y="${a.y}" width="${o}" height="${h}" 
              fill="none" stroke="var(--brighten-primary)" stroke-width="2"/>
      </svg>
      <div class="crop-handles" style="position: absolute; inset: 0; pointer-events: auto;">
        ${this.renderCropHandles(a.x,a.y,o,h)}
      </div>
    `,t.appendChild(e),this.cropOverlay=e,this.setupCropHandlers(e)}renderCropHandles(t,e,i,r){const a=[{pos:"nw",x:t-5,y:e-5},{pos:"n",x:t+i/2-5,y:e-5},{pos:"ne",x:t+i-5,y:e-5},{pos:"e",x:t+i-5,y:e+r/2-5},{pos:"se",x:t+i-5,y:e+r-5},{pos:"s",x:t+i/2-5,y:e+r-5},{pos:"sw",x:t-5,y:e+r-5},{pos:"w",x:t-5,y:e+r/2-5}],n={nw:"nwse-resize",n:"ns-resize",ne:"nesw-resize",e:"ew-resize",se:"nwse-resize",s:"ns-resize",sw:"nesw-resize",w:"ew-resize"};return a.map(o=>`
      <div data-crop-handle="${o.pos}" style="
        position: absolute;
        left: ${o.x}px;
        top: ${o.y}px;
        width: 10px;
        height: 10px;
        background: white;
        border: 2px solid var(--brighten-primary);
        cursor: ${n[o.pos]};
      "></div>
    `).join("")+`
      <div data-crop-handle="move" style="
        position: absolute;
        left: ${t}px;
        top: ${e}px;
        width: ${i}px;
        height: ${r}px;
        cursor: move;
      "></div>
    `}setupCropHandlers(t){this.cropMouseMoveHandler||(this.cropMouseMoveHandler=e=>{var h,c,l,g;const i=this.cropDragState;if(!i.active||!i.startRect||!this.cropRect)return;const r=this.editor.getZoom(),s=(e.clientX-i.startX)/r,a=(e.clientY-i.startY)/r,n=this.editor.getCanvasSize(),o=20;if(i.handle==="move")this.cropRect.x=Math.max(0,Math.min(i.startRect.x+s,n.width-this.cropRect.width)),this.cropRect.y=Math.max(0,Math.min(i.startRect.y+a,n.height-this.cropRect.height));else{if((h=i.handle)!=null&&h.includes("w")){const d=i.startRect.x+s,u=i.startRect.width-s;u>=o&&d>=0&&(this.cropRect.x=d,this.cropRect.width=u)}if((c=i.handle)!=null&&c.includes("e")){const d=i.startRect.width+s;d>=o&&i.startRect.x+d<=n.width&&(this.cropRect.width=d)}if((l=i.handle)!=null&&l.includes("n")){const d=i.startRect.y+a,u=i.startRect.height-a;u>=o&&d>=0&&(this.cropRect.y=d,this.cropRect.height=u)}if((g=i.handle)!=null&&g.includes("s")){const d=i.startRect.height+a;d>=o&&i.startRect.y+d<=n.height&&(this.cropRect.height=d)}}this.updateCropOverlayPosition()},document.addEventListener("mousemove",this.cropMouseMoveHandler)),this.cropMouseUpHandler||(this.cropMouseUpHandler=()=>{this.cropDragState.active=!1,this.cropDragState.handle=null,this.cropDragState.startRect=null},document.addEventListener("mouseup",this.cropMouseUpHandler)),t.addEventListener("mousedown",e=>{const r=e.target.dataset.cropHandle;!r||!this.cropRect||(this.cropDragState.active=!0,this.cropDragState.handle=r,this.cropDragState.startX=e.clientX,this.cropDragState.startY=e.clientY,this.cropDragState.startRect={...this.cropRect},e.preventDefault())})}updateCropOverlayPosition(){if(!this.cropOverlay||!this.cropRect)return;const t=this.editor.getZoom(),e=this.editor.getPan(),i=(g,d)=>({x:g*t+e.x,y:d*t+e.y}),r=i(this.cropRect.x,this.cropRect.y),s=i(this.cropRect.x+this.cropRect.width,this.cropRect.y+this.cropRect.height),a=s.x-r.x,n=s.y-r.y,o=10,h=this.cropOverlay.querySelector("svg");h&&h.querySelectorAll("rect").forEach((d,u)=>{u!==0&&(d.setAttribute("x",String(r.x)),d.setAttribute("y",String(r.y)),d.setAttribute("width",String(a)),d.setAttribute("height",String(n)))});const c=this.cropOverlay.querySelectorAll("[data-crop-handle]"),l={nw:{x:r.x-o/2,y:r.y-o/2},n:{x:r.x+a/2-o/2,y:r.y-o/2},ne:{x:r.x+a-o/2,y:r.y-o/2},e:{x:r.x+a-o/2,y:r.y+n/2-o/2},se:{x:r.x+a-o/2,y:r.y+n-o/2},s:{x:r.x+a/2-o/2,y:r.y+n-o/2},sw:{x:r.x-o/2,y:r.y+n-o/2},w:{x:r.x-o/2,y:r.y+n/2-o/2},move:{x:r.x,y:r.y}};c.forEach(g=>{const d=g.dataset.cropHandle;d&&l[d]&&(g.style.left=`${l[d].x}px`,g.style.top=`${l[d].y}px`,d==="move"&&(g.style.width=`${a}px`,g.style.height=`${n}px`))})}removeCropOverlay(){this.cropMouseMoveHandler&&(document.removeEventListener("mousemove",this.cropMouseMoveHandler),this.cropMouseMoveHandler=null),this.cropMouseUpHandler&&(document.removeEventListener("mouseup",this.cropMouseUpHandler),this.cropMouseUpHandler=null),this.cropDragState={active:!1,handle:null,startX:0,startY:0,startRect:null},this.cropOverlay&&(this.cropOverlay.remove(),this.cropOverlay=null)}setCropRatio(t){if(this.cropRect){if(t){const e=this.cropRect.width*this.cropRect.height,i=Math.sqrt(e*t),r=i/t,s=this.cropRect.x+this.cropRect.width/2,a=this.cropRect.y+this.cropRect.height/2;this.cropRect.width=i,this.cropRect.height=r,this.cropRect.x=s-i/2,this.cropRect.y=a-r/2}this.renderCropOverlay()}}applyCrop(){if(!this.cropRect)return;const e=this.editor.getLayerManager().getLayers().find(a=>a.type==="image");if(!e||e.type!=="image")return;const i=e.source;i instanceof HTMLImageElement?i.naturalWidth:i.width,i instanceof HTMLImageElement?i.naturalHeight:i.height;const r=document.createElement("canvas");r.width=Math.round(this.cropRect.width),r.height=Math.round(this.cropRect.height),r.getContext("2d").drawImage(i,this.cropRect.x,this.cropRect.y,this.cropRect.width,this.cropRect.height,0,0,this.cropRect.width,this.cropRect.height),this.editor.getLayerManager().updateLayer(e.id,{source:r}),this.editor.getCanvasManager().setCanvasSize({width:Math.round(this.cropRect.width),height:Math.round(this.cropRect.height)}),this.editor.saveToHistory("Crop"),this.removeCropOverlay(),this.cropRect=null,this.originalImageData=null,this.showPanel(null),this.setTool("select")}cancelCrop(){this.removeCropOverlay(),this.cropRect=null,this.showPanel(null),this.setTool("select")}rotateImage(t){const i=this.editor.getLayerManager().getLayers().find(c=>c.type==="image");if(!i||i.type!=="image")return;const r=i.source,s=r instanceof HTMLImageElement?r.naturalWidth:r.width,a=r instanceof HTMLImageElement?r.naturalHeight:r.height,n=document.createElement("canvas"),o=Math.abs(t)===90||Math.abs(t)===270;n.width=o?a:s,n.height=o?s:a;const h=n.getContext("2d");h.translate(n.width/2,n.height/2),h.rotate(t*Math.PI/180),h.drawImage(r,-s/2,-a/2),this.editor.getLayerManager().updateLayer(i.id,{source:n}),this.editor.getCanvasManager().setCanvasSize({width:n.width,height:n.height}),this.editor.saveToHistory(`Rotate ${t}°`),this.originalImageData=null}flipImage(t){const i=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!i||i.type!=="image")return;const r=i.source,s=r instanceof HTMLImageElement?r.naturalWidth:r.width,a=r instanceof HTMLImageElement?r.naturalHeight:r.height,n=document.createElement("canvas");n.width=s,n.height=a;const o=n.getContext("2d");t==="horizontal"?(o.translate(s,0),o.scale(-1,1)):(o.translate(0,a),o.scale(1,-1)),o.drawImage(r,0,0),this.editor.getLayerManager().updateLayer(i.id,{source:n}),this.editor.saveToHistory(`Flip ${t}`),this.originalImageData=null}setAiProcessing(t){const e=this.root.querySelector(".brighten-canvas-container");e&&(t&&!this.aiGlowElement?(this.aiGlowElement=document.createElement("div"),this.aiGlowElement.className="brighten-ai-border",e.appendChild(this.aiGlowElement)):!t&&this.aiGlowElement&&(this.aiGlowElement.remove(),this.aiGlowElement=null))}getApiHeaders(){const t={"Content-Type":"application/json"};return this.config.apiKey&&(t.Authorization=`Bearer ${this.config.apiKey}`),t}async removeBackground(){if(!this.config.apiEndpoint){console.error("API endpoint not configured");return}const e=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height,r.getContext("2d").drawImage(i,0,0);const a=r.toDataURL("image/png"),n=this.root.querySelector('[data-action="remove-background"]'),o=()=>{this.setAiProcessing(!1),n&&(n.disabled=!1,n.innerHTML=`${p.magic} Remove Background`)};this.setAiProcessing(!0),n&&(n.disabled=!0,n.innerHTML=`${p.magic} Processing...`);try{const h=await fetch(`${this.config.apiEndpoint}/api/v1/background-remove`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a})});if(!h.ok){const g=await h.json();throw new Error(g.error||"Failed to remove background")}const c=await h.json(),l=new Image;l.onload=()=>{this.editor.getLayerManager().updateLayer(e.id,{source:l}),this.editor.saveToHistory("Remove background"),this.originalImageData=null,o()},l.onerror=()=>{throw new Error("Failed to load processed image")},l.src=c.image}catch(h){console.error("Background removal failed:",h),o(),alert(h instanceof Error?h.message:"Background removal failed")}}async unblur(){if(!this.config.apiEndpoint){console.error("API endpoint not configured");return}const e=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height,r.getContext("2d").drawImage(i,0,0);const a=r.toDataURL("image/png"),n=this.root.querySelector('[data-action="unblur"]'),o=()=>{this.setAiProcessing(!1),n&&(n.disabled=!1,n.innerHTML=`${p.focus} Unblur / Enhance`)};this.setAiProcessing(!0),n&&(n.disabled=!0,n.innerHTML=`${p.focus} Processing...`);try{const h=await fetch(`${this.config.apiEndpoint}/api/v1/unblur`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a})});if(!h.ok){const u=await h.json();throw new Error(u.error||"Failed to unblur image")}const c=await h.json(),l=r.width,g=r.height,d=new Image;d.onload=()=>{const u=document.createElement("canvas");u.width=l,u.height=g,u.getContext("2d").drawImage(d,0,0,l,g),this.editor.getLayerManager().updateLayer(e.id,{source:u}),this.editor.saveToHistory("Unblur image"),this.originalImageData=null,o()},d.onerror=()=>{o(),alert("Failed to load processed image")},d.src=c.image}catch(h){console.error("Unblur failed:",h),o(),alert(h instanceof Error?h.message:"Unblur failed")}}async upscale(){if(!this.config.apiEndpoint){console.error("API endpoint not configured");return}const e=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height,r.getContext("2d").drawImage(i,0,0);const a=r.toDataURL("image/png"),n=this.root.querySelector('[data-action="upscale"]'),o=()=>{this.setAiProcessing(!1),n&&(n.disabled=!1,n.innerHTML=`${p.expand} Upscale 4x`)};this.setAiProcessing(!0),n&&(n.disabled=!0,n.innerHTML=`${p.expand} Processing...`);try{const h=await fetch(`${this.config.apiEndpoint}/api/v1/upscale`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a})});if(!h.ok){const g=await h.json();throw new Error(g.error||"Failed to upscale image")}const c=await h.json(),l=new Image;l.onload=()=>{this.editor.getCanvasManager().setCanvasSize({width:l.naturalWidth,height:l.naturalHeight}),this.editor.getLayerManager().updateLayer(e.id,{source:l}),this.editor.saveToHistory("Upscale image 4x"),this.originalImageData=null,o()},l.onerror=()=>{o(),alert("Failed to load processed image")},l.src=c.image}catch(h){console.error("Upscale failed:",h),o(),alert(h instanceof Error?h.message:"Upscale failed")}}async colorize(){if(!this.config.apiEndpoint){console.error("API endpoint not configured");return}const e=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height,r.getContext("2d").drawImage(i,0,0);const a=r.toDataURL("image/png"),n=this.root.querySelector('[data-action="colorize"]'),o=()=>{this.setAiProcessing(!1),n&&(n.disabled=!1,n.innerHTML=`${p.palette} Colorize`)};this.setAiProcessing(!0),n&&(n.disabled=!0,n.innerHTML=`${p.palette} Processing...`);try{const h=await fetch(`${this.config.apiEndpoint}/api/v1/colorize`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a})});if(!h.ok){const u=await h.json();throw new Error(u.error||"Failed to colorize image")}const c=await h.json(),l=r.width,g=r.height,d=new Image;d.onload=()=>{const u=document.createElement("canvas");u.width=l,u.height=g,u.getContext("2d").drawImage(d,0,0,l,g),this.editor.getLayerManager().updateLayer(e.id,{source:u}),this.editor.saveToHistory("Colorize image"),this.originalImageData=null,o()},d.onerror=()=>{o(),alert("Failed to load colorized image")},d.src=c.image}catch(h){console.error("Colorize failed:",h),o(),alert(h instanceof Error?h.message:"Colorize failed")}}async restore(){if(!this.config.apiEndpoint)return;const e=this.editor.getLayerManager().getLayers().find(s=>s.type==="image");if(!e)return;const i=this.root.querySelector('[data-action="restore"]'),r=()=>{this.setAiProcessing(!1),i&&(i.disabled=!1,i.innerHTML=`${p.magic} Restore Photo`)};this.setAiProcessing(!0),i&&(i.disabled=!0,i.innerHTML=`${p.magic} Processing...`);try{const s=e.source,a=document.createElement("canvas"),n=s instanceof HTMLImageElement?s.naturalWidth:s.width,o=s instanceof HTMLImageElement?s.naturalHeight:s.height;a.width=n,a.height=o,a.getContext("2d").drawImage(s,0,0);const c=a.toDataURL("image/jpeg",.95),l=await fetch(`${this.config.apiEndpoint}/api/v1/restore`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:c})}),g=await l.json();if(!l.ok)throw new Error(g.error||"Failed to restore image");const d=new Image;d.onload=()=>{const u=document.createElement("canvas");u.width=n,u.height=o,u.getContext("2d").drawImage(d,0,0,n,o),this.editor.getLayerManager().updateLayer(e.id,{source:u}),this.editor.saveToHistory("Restore image"),this.originalImageData=null,r()},d.onerror=()=>{r(),alert("Failed to load restored image")},d.src=g.image}catch(s){console.error("Restore failed:",s),r(),alert(s instanceof Error?s.message:"Restore failed")}}async analyze(){if(!this.config.apiEndpoint){console.error("API endpoint not configured");return}const e=this.editor.getLayerManager().getLayers().find(h=>h.type==="image");if(!e||e.type!=="image")return;const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height,r.getContext("2d").drawImage(i,0,0);const a=r.toDataURL("image/png"),n=this.root.querySelector('[data-action="analyze"]'),o=()=>{this.setAiProcessing(!1),n&&(n.disabled=!1,n.innerHTML=`<span style="display: inline-block; width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;">${p.scan}</span> Analyze Image`)};this.setAiProcessing(!0),n&&(n.disabled=!0,n.innerHTML=`<span style="display: inline-block; width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;">${p.scan}</span> Analyzing...`);try{const h=await fetch(`${this.config.apiEndpoint}/api/v1/analyze`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a})});if(!h.ok){const g=await h.json();throw new Error(g.error||"Failed to analyze image")}const c=await h.json();o();const l=c.caption.replace(/^Caption:\s*/i,"");this.lastAnalysisCaption=l,this.showAnalysisResult(l)}catch(h){console.error("Analysis failed:",h),o(),alert(h instanceof Error?h.message:"Analysis failed")}}showAnalysisResult(t){var r;const e=this.root.querySelector(".brighten-analysis-result");e&&e.remove();const i=document.createElement("div");i.className="brighten-analysis-result",i.style.cssText=`
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--brighten-surface);
      border: 1px solid var(--brighten-border);
      border-radius: 8px;
      padding: 16px 20px;
      max-width: 400px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      z-index: 1000;
      animation: brighten-fade-in 0.2s ease-out;
    `,i.innerHTML=`
      <div style="display: flex; align-items: flex-start; gap: 12px;">
        <div style="flex-shrink: 0; width: 24px; height: 24px; color: var(--brighten-primary);">
          ${p.sparkles}
        </div>
        <div style="flex: 1;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 4px;">AI Analysis</div>
          <div style="font-size: 14px; color: var(--brighten-text); line-height: 1.4;">${t}</div>
        </div>
        <button class="brighten-btn brighten-btn-icon" style="padding: 4px; flex-shrink: 0;" data-action="close-analysis">
          ${p.close}
        </button>
      </div>
    `,(r=i.querySelector('[data-action="close-analysis"]'))==null||r.addEventListener("click",()=>{i.remove()}),setTimeout(()=>{i.parentNode&&(i.style.opacity="0",i.style.transition="opacity 0.2s ease-out",setTimeout(()=>i.remove(),200))},1e4),this.root.appendChild(i)}startInpaintMode(){const e=this.editor.getLayerManager().getLayers().find(u=>u.type==="image");if(!e||e.type!=="image")return;this.inpaintMode=!0;const i=e.source,r=i instanceof HTMLImageElement?i.naturalWidth:i.width,s=i instanceof HTMLImageElement?i.naturalHeight:i.height;this.maskCanvas=document.createElement("canvas"),this.maskCanvas.width=r,this.maskCanvas.height=s,this.maskCtx=this.maskCanvas.getContext("2d"),this.maskCtx.fillStyle="black",this.maskCtx.fillRect(0,0,r,s);const a=this.root.querySelector(".brighten-canvas-container");if(!a)return;const n=this.editor.getZoom(),o=this.editor.getPan(),h=r*n,c=s*n;this.maskOverlay=document.createElement("div"),this.maskOverlay.className="brighten-mask-overlay",this.maskOverlay.style.cssText=`position: absolute; left: ${o.x}px; top: ${o.y}px; width: ${h}px; height: ${c}px; cursor: none; z-index: 100;`;const l=document.createElement("canvas");l.style.cssText="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;";const g=document.createElement("canvas");g.style.cssText="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;";const d=document.createElement("canvas");d.style.cssText="position: absolute; pointer-events: none; display: none;",this.maskOverlay.appendChild(l),this.maskOverlay.appendChild(g),this.maskOverlay.appendChild(d),a.appendChild(this.maskOverlay),this.setupInpaintDrawing(l,g,d),this.showPanel("ai")}setupInpaintDrawing(t,e,i){if(!this.maskOverlay||!this.maskCanvas||!this.maskCtx)return;const r=[];let s=null,a=!1,n=null;const o=2e3,h={r:200,g:200,b:210},c=(m,y,v)=>{y/=100,v/=100;const w=M=>(M+m/30)%12,k=y*Math.min(v,1-v),x=M=>v-k*Math.max(-1,Math.min(w(M)-3,Math.min(9-w(M),1)));return{r:Math.round(255*x(0)),g:Math.round(255*x(8)),b:Math.round(255*x(4))}},l=()=>{if(!this.inpaintMode)return;const m=this.maskOverlay.getBoundingClientRect();(t.width!==m.width||t.height!==m.height)&&(t.width=m.width,t.height=m.height,e.width=m.width,e.height=m.height);const y=t.getContext("2d"),v=e.getContext("2d");y.clearRect(0,0,t.width,t.height),v.clearRect(0,0,e.width,e.height);const w=Date.now(),k=t.width/this.maskCanvas.width,x=t.height/this.maskCanvas.height;if(r.length>0){y.save(),y.beginPath();for(const C of r)y.moveTo(C.x*k+C.radius*k,C.y*x),y.arc(C.x*k,C.y*x,C.radius*k,0,Math.PI*2);y.clip();const M=r.reduce((C,P)=>C+(w-P.timestamp),0)/r.length,z=Math.min(M/o,1),A=z*300%360,$=90-z*50,T=60+z*25;let E,H,D;if(z>=1)E=h.r,H=h.g,D=h.b;else{const C=c(A,$,T),P=Math.pow(z,2);E=Math.round(C.r*(1-P)+h.r*P),H=Math.round(C.g*(1-P)+h.g*P),D=Math.round(C.b*(1-P)+h.b*P)}y.fillStyle=`rgba(${E}, ${H}, ${D}, 0.4)`,y.fillRect(0,0,t.width,t.height),y.restore()}if(!a&&r.length>0){n===null&&(n=w);const M=w-n,z=M*.1%360,A=.12+Math.sin(M*.005)*.08,$=this.maskCtx.getImageData(0,0,this.maskCanvas.width,this.maskCanvas.height),T=v.createImageData(e.width,e.height);for(let E=0;E<e.height;E++)for(let H=0;H<e.width;H++){const D=Math.floor(H/k),P=(Math.floor(E/x)*this.maskCanvas.width+D)*4;if($.data[P]>128){const ht=(z+H*.5+E*.3)%360,q=c(ht,70,85),j=(E*e.width+H)*4;T.data[j]=q.r,T.data[j+1]=q.g,T.data[j+2]=q.b,T.data[j+3]=Math.round(A*255)}}v.putImageData(T,0,0)}else n=null;s=requestAnimationFrame(l)};l();const g=m=>{const y=this.maskOverlay.getBoundingClientRect(),v=this.maskCanvas.width/y.width,w=this.maskCanvas.height/y.height,k=(m.clientX-y.left)*v,x=(m.clientY-y.top)*w;return{x:k,y:x,scaleX:v,scaleY:w}},d=(m,y,v,w,k)=>{const x=Math.sqrt((v-m)**2+(w-y)**2),M=Math.max(1,Math.floor(x/3)),z=Date.now(),A=this.inpaintBrushSize*k;for(let $=0;$<=M;$++){const T=$/M;r.push({x:m+(v-m)*T,y:y+(w-y)*T,radius:A/2,timestamp:z+$*5})}this.maskCtx.strokeStyle="white",this.maskCtx.lineWidth=A,this.maskCtx.lineCap="round",this.maskCtx.lineJoin="round",this.maskCtx.beginPath(),this.maskCtx.moveTo(m,y),this.maskCtx.lineTo(v,w),this.maskCtx.stroke()},u=(m,y,v)=>{const w=this.inpaintBrushSize*v;r.push({x:m,y,radius:w/2,timestamp:Date.now()}),this.maskCtx.fillStyle="white",this.maskCtx.beginPath(),this.maskCtx.arc(m,y,w/2,0,Math.PI*2),this.maskCtx.fill()},L=m=>{if(!this.inpaintDrawState.drawing||!this.maskCtx)return;const{x:y,y:v,scaleX:w}=g(m);d(this.inpaintDrawState.lastX,this.inpaintDrawState.lastY,y,v,w),this.inpaintDrawState.lastX=y,this.inpaintDrawState.lastY=v};this.maskOverlay.addEventListener("mousedown",m=>{const{x:y,y:v,scaleX:w}=g(m);this.inpaintDrawState={drawing:!0,lastX:y,lastY:v},a=!0,u(y,v,w)});const S=m=>{const y=this.maskOverlay.getBoundingClientRect(),v=this.inpaintBrushSize,k=v+4*2;i.width=k,i.height=k,i.style.width=`${k}px`,i.style.height=`${k}px`;const x=i.getContext("2d");x.clearRect(0,0,k,k);const M=k/2,z=v/2;x.beginPath(),x.arc(M,M,z,0,Math.PI*2),x.strokeStyle="rgba(0, 0, 0, 0.7)",x.lineWidth=2,x.stroke(),x.beginPath(),x.arc(M,M,z,0,Math.PI*2),x.strokeStyle="rgba(255, 255, 255, 0.9)",x.lineWidth=1,x.stroke(),i.style.left=`${m.clientX-y.left-M}px`,i.style.top=`${m.clientY-y.top-M}px`,i.style.display="block"};this.maskOverlay.addEventListener("mousemove",m=>{L(m),S(m)}),this.maskOverlay.addEventListener("mouseenter",m=>{S(m)}),this.maskOverlay.addEventListener("mouseup",()=>{this.inpaintDrawState.drawing=!1,a=!1}),this.maskOverlay.addEventListener("mouseleave",()=>{this.inpaintDrawState.drawing=!1,a=!1,i.style.display="none"});const I=()=>{s&&cancelAnimationFrame(s)};this.maskOverlay._cleanup=I}cancelInpaintMode(){if(this.inpaintMode=!1,this.maskCanvas=null,this.maskCtx=null,this.maskOverlay){const t=this.maskOverlay._cleanup;t&&t(),this.maskOverlay.remove(),this.maskOverlay=null}this.showPanel("ai")}async applyInpaint(){if(!this.config.apiEndpoint||!this.maskCanvas){this.cancelInpaintMode();return}const e=this.editor.getLayerManager().getLayers().find(g=>g.type==="image");if(!e||e.type!=="image"){this.cancelInpaintMode();return}const i=e.source,r=document.createElement("canvas");r.width=i instanceof HTMLImageElement?i.naturalWidth:i.width,r.height=i instanceof HTMLImageElement?i.naturalHeight:i.height;const s=r.getContext("2d");s.fillStyle="white",s.fillRect(0,0,r.width,r.height),s.drawImage(i,0,0);const a=r.toDataURL("image/jpeg",.95),n=document.createElement("canvas");n.width=r.width,n.height=r.height;const o=n.getContext("2d");o.fillStyle="black",o.fillRect(0,0,n.width,n.height),o.drawImage(this.maskCanvas,0,0,n.width,n.height);const h=n.toDataURL("image/jpeg",1),c=this.root.querySelector('[data-action="apply-inpaint"]'),l=this.root.querySelector('[data-action="cancel-inpaint"]');this.setAiProcessing(!0),c&&(c.disabled=!0,c.textContent="Processing..."),l&&(l.disabled=!0);try{const g=await fetch(`${this.config.apiEndpoint}/api/v1/inpaint`,{method:"POST",headers:this.getApiHeaders(),body:JSON.stringify({image:a,options:{mask:h}})});if(!g.ok){const I=await g.json();throw new Error(I.error||"Failed to remove objects")}const d=await g.json(),u=r.width,L=r.height,S=new Image;S.onload=()=>{const I=document.createElement("canvas");I.width=u,I.height=L,I.getContext("2d").drawImage(S,0,0,u,L),this.editor.getLayerManager().updateLayer(e.id,{source:I}),this.editor.saveToHistory("Remove objects"),this.originalImageData=null,this.setAiProcessing(!1),this.cancelInpaintMode()},S.onerror=()=>{this.setAiProcessing(!1),this.cancelInpaintMode(),alert("Failed to load processed image")},S.src=d.image}catch(g){console.error("Inpaint failed:",g),this.setAiProcessing(!1),this.cancelInpaintMode(),alert(g instanceof Error?g.message:"Remove objects failed")}}openFilePicker(){const t=document.createElement("input");t.type="file",t.accept="image/*",t.onchange=async()=>{var e;(e=t.files)!=null&&e[0]&&(await this.editor.loadFromFile(t.files[0]),this.resetAdjustments(),this.originalImageData=null,this.currentPreset=null,this.filterPreviewSource=null,this.filterPreviewCache.clear())},t.click()}async exportImage(){const t=await this.editor.export({format:"png",quality:.92});if(this.config.onExport)this.config.onExport(t);else{const e=URL.createObjectURL(t),i=document.createElement("a");i.href=e,i.download="edited-image.png",i.click(),URL.revokeObjectURL(e)}}async saveImage(){if(!this.config.onSave||this.isSaving)return;const t=this.root.querySelector('[data-action="save"]'),e=t==null?void 0:t.innerHTML;try{this.isSaving=!0,t&&(t.disabled=!0,t.innerHTML=`${p.save} Saving...`);const i=await this.editor.export({format:"png",quality:.92}),r={};this.lastAnalysisCaption&&(r.caption=this.lastAnalysisCaption);const s=await this.config.onSave(i,r);return t&&(t.innerHTML=`${p.check} Saved`,setTimeout(()=>{t.innerHTML=e||`${p.save} Save`},2e3)),s}catch(i){throw console.error("Save failed:",i),t&&(t.innerHTML=`${p.close} Failed`,setTimeout(()=>{t.innerHTML=e||`${p.save} Save`},2e3)),i}finally{this.isSaving=!1,t&&(t.disabled=!1)}}addText(){const t=this.editor.getLayerManager(),e=this.editor.getCanvasSize();t.addTextLayer("Double-click to edit",{fontSize:32,color:"#ffffff",transform:{x:e.width/2-100,y:e.height/2-20,scaleX:1,scaleY:1,rotation:0,skewX:0,skewY:0}}),this.editor.saveToHistory("Add text"),this.showPanel("layers")}addLayer(){this.editor.getLayerManager().addDrawingLayer({name:"New Layer"}),this.editor.saveToHistory("Add layer"),this.showPanel("layers")}addShape(t){const e=this.editor.getLayerManager(),i=this.editor.getCanvasSize();e.addShapeLayer(t,{fill:"#3b82f6",transform:{x:i.width/2-50,y:i.height/2-50,scaleX:1,scaleY:1,rotation:0,skewX:0,skewY:0}}),this.editor.saveToHistory(`Add ${t}`),this.showPanel("layers")}toggleLayerVisibility(t){const e=this.editor.getLayerManager().getLayer(t);e&&this.editor.getLayerManager().updateLayer(t,{visible:!e.visible})}async loadImage(t){await this.editor.loadImage(t),this.resetAdjustments(),this.originalImageData=null,this.currentPreset=null,this.filterPreviewSource=null,this.filterPreviewCache.clear()}getEditor(){return this.editor}destroy(){this.keyboardHandler&&document.removeEventListener("keydown",this.keyboardHandler),this.removeCropOverlay(),this.editor.destroy(),this.root.remove()}}function K(b){return new O(b)}function G(b){return new U(b)}const ot={createEditor:K,createEditorUI:G,Editor:O,EditorUI:U};f.AIManager=rt,f.AIProvider=F,f.BaseTool=B,f.BrushTool=V,f.CanvasManager=W,f.CropTool=Q,f.Editor=O,f.EditorUI=U,f.EventEmitter=R,f.FilterEngine=N,f.HistoryManager=Y,f.ImageLoader=_,f.LayerManager=X,f.PluginManager=st,f.RemoveBgProvider=et,f.ReplicateProvider=it,f.TransformTool=tt,f.createEditor=K,f.createEditorUI=G,f.default=ot,Object.defineProperties(f,{__esModule:{value:!0},[Symbol.toStringTag]:{value:"Module"}})});
//# sourceMappingURL=brighten.umd.js.map
