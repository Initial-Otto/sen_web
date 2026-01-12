// src/utils/eventBus.js
import mitt from 'mitt';
// 创建 mitt 实例
const emitter = mitt();
// 导出这个实例
export default emitter;