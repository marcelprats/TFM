import { defineStore } from 'pinia';

export const useErrorStore = defineStore('error', {
  state: () => ({
    message: '',
    visible: false
  }),
  actions: {
    show(msg: string) {
      this.message = msg;
      this.visible = true;
    },
    hide() {
      this.visible = false;
      this.message = '';
    }
  }
});
