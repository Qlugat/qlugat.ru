function getStem(word) {
    return word.toLowerCase()
        .replace(/â/g, 'a')
        .replace(/ç/g, 'c')
        .replace(/ğ/g, 'g')
        .replace(/ı/g, 'i')
        .replace(/ñ/g, 'n')
        .replace(/ö/g, 'o')
        .replace(/q/g, 'k')
        .replace(/ş/g, 's')
        .replace(/ü/g, 'u')
        .replace(/ё/g, 'е') // Russian yo
}

function withStems(list) {
    const outList = list.map(function(item) {
        return { word: item, stem: getStem(item) };
    });
    return outList;
}

var app = new Vue({
    el: '#app',
    data: {
        start: true,
        pos: -1,
        word: '',
        dictEntry: null,
        list: [],
        suggestDb: {}
    },
    methods: {
        submit: function (word) {
            fetch('/get_json?word=' + word).then(response => response.json()).then(data => {
                this.dictEntry = (Object.keys(data).length === 0) ? null : data;
            });
        },
        submitEnter: function(event) {
            const word = this.list.length ? this.list[this.pos].word : this.word;
            word && this.submit(word);
        },
        submitButton: function(event) {
            this.submit(this.word);
        },
        upHandler: function (event) {
          event.preventDefault();
          this.pos && this.pos--;
        },
        downHandler: function (event) {
            event.preventDefault();
            ((this.pos + 1) < this.list.length) && this.pos++;
        },
        wordChangedHandler: function(event) {
            this.start = false;
            const word = event.target.value;
            const stem = getStem(word);
            if (word.length === 0) {
                this.list = [];
                this.pos = -1;
                this.dictEntry = null;
            } else if (word.length === 1 && !this.suggestDb[stem]) {
                fetch('/suggest?token=' + word).then(response => response.json()).then(data => {
                    this.suggestDb[stem] = withStems(data);
                    this.updateList(getStem(this.word));
                });
            } else {
                var suggestList = this.suggestDb[stem[0]];
                if (suggestList) {
                    this.updateList(stem);
                }
            }
        },
        updateList: function(stem) {
            var suggestList = this.suggestDb[stem[0]];
            this.list = stem ?
                suggestList.filter(item => {
                    return item.stem.startsWith(stem)
                }).slice(0, 20) : [];
            this.pos = 0;
            if (this.list.length == 1) {
                this.submit(this.list[0].word);
            }
        },
        itemClick: function(item, index) {
            this.word = item;
            this.pos = index;
            this.dictEntry = null;
            this.submit(item);
        },
        accentize: function(word, article) {
            var pos = article.accent_pos;
            if (pos) {
                return word.substring(0, pos) + "\u0301" + word.substring(pos);
            } else {
                return word;
            }
        },
        json2html: function(input, word, dictEntry) {
            var pos = dictEntry.shortening_pos;
            if (pos) {
                input = input.replace(/~/g, word.substring(0, pos));
            }
//            input = input.replace(/^(.[^)].+? )-( .+?)$/mg,'<span class="example">$1─$2</span>');
//            input = input.replace(/\/(.+?)\//g,'<i class="spec">$1</i>');
//            input = input.replace(/^(ср|см)\. (.+)$/mg,
//                function(str, p1, p2, offset, s) {
//                    var words = p2.split(/\s*,\s*/);
//                    var links = '';
//                    words.each(function(val, index, arr) {
//                        links += '<a href="#" onclick="return qlugat.insert_and_submit(\''+val+'\');" >'+val+'</a>, ';
//                    });
//                    return '<i class="link">'+p1+'.</i> '+links.slice(0, -2);
//                });
            input = input.replace(/(лингв|перен|физ|хим|бот|биол|зоо|грам|геогр|астр|шк|мат|анат|ирон|этн|стр|рел|посл|уст)\./g,'<i class="spec">$&</i>');
            input = input.replace('◊', '\n◊\n');
            input = input.replace(/\\n/g, '<br/>');
            input = input.replace(/\n/g, '<br/>');
            input = input.replace(/; /g, '<br/>');
            return input;
        }
    }
})
