class TestApiScript {
    constructor() {
        if (!window.testApiScriptData) return;
        this.params = window.testApiScriptData;
        this.variation = this.params.defaults;
        this.default_msg = 'Товара нет в наличии';
        this.img = document.querySelector('img.wp-post-image');
        this.price = document.querySelector('.woocommerce-Price-amount bdi');
        this.msg_container = document.querySelector('#test-api-item__message');
        this.btns = document.querySelectorAll('.test-api-item__button');
        if (!this.btns) return;

        this.update();
        this.btns.forEach((btn) => {
            btn.addEventListener('click', (e) => this.on_click(e));
        });
    }


    on_click(e) {
        if (e.target.classList.contains('active')) return;
        let color = this.variation.color, size = this.variation.size, get_color = false;
        if (e.target.dataset.color) {
            color = e.target.dataset.color;
            get_color = true;
        }
        if (e.target.dataset.size) {
            size = e.target.dataset.size;
        }
        this.get_data(color, size, get_color).then(response => {
            if (response.success) {
                this.variation = response.data;
                this.update();
            } else {
                this.update_message(response.data.message ?? this.default_msg);
                return;
            }
        })
    }

    update() {
        if (!this.variation.color || !this.variation.size) return;
        this.update_message();
        if (this.variation.image) {
            this.img.setAttribute('src', this.variation.image);
        }
        if (this.variation.price) {
            const currency = this.price.querySelector('.woocommerce-Price-currencySymbol');
            this.price.innerHTML = '';
            const span = document.createElement('span');
            span.textContent = this.variation.price + '\u00A0';
            this.price.appendChild(span);
            if (currency) {
                this.price.appendChild(currency);
            }
        }

        this.update_btns(this.variation.color, this.variation.size, this.variation.compatibility);
    }

    update_message(message = '') {
        if (message) {
            this.msg_container.textContent = message;
        } else {
            if (this.variation.available && this.variation.stock) {
                this.msg_container.textContent = 'Осталось ' + this.variation.stock + ' шт.'
            } else {
                this.msg_container.textContent = this.default_msg;
            }
        }
    }

    update_btns(color, size, compatibility = []) {
        this.btns.forEach(btn => {
            if (btn.dataset.color) {
                if (btn.dataset.color === color) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }

            if (btn.dataset.size) {
                if (btn.dataset.size === size) {
                    btn.classList.add('active')
                } else {
                    btn.classList.remove('active');
                }

                if (compatibility[btn.dataset.size]) {
                    btn.removeAttribute('disabled');
                    btn.removeAttribute('title');
                } else {
                    btn.setAttribute('disabled', true);
                    btn.setAttribute('title', 'Товара нет в наличии');
                }
            }

        })
    }

    async get_data(color, size, get_color) {
        let fd = new FormData();
        fd.append('action', 'test_api_action');
        fd.append('_wpnonce', this.params.nonce);
        fd.append('product_id', this.params.product_id);
        if (color && size) {
            fd.append('variation', color + '_' + size);
        }
        if (get_color) {
            fd.append('get_color', color);
        }
        return await fetch(window.testApiScriptData.ajax_url, {
            method: 'POST',
            body: fd
        }).then(response => response.json());
    }
}


window.addEventListener('DOMContentLoaded', function () {
    window.TestApiScript = new TestApiScript();
});