const app = new Vue({
    el: '#app',
    data: {
        uri: "http://localhost:3030",
        curTab: "login", //'login', 'vote', 'suggestions', 'newVote'
        user: {
            name: "",
            password: "",
            votes: [],
        },
        loggedIn: false,
        newVote: {
            title: "",
            limit: 3,
            suggestionsCloses: {date: "2019-05-31", time: "16:00"},
            votingCloses: {date: "2019-05-31", time: "23:00"},
        },
        newItem: {name: "", link: "", user: ""},
        currentVote: {
            title: "Title", 
            date: 0,
            suggestionsCloses: {date: "2019-05-31", time: "16:00"},
            votingCloses: {date: "2019-05-31", time: "23:00"},
            limit: 3,
            items: [
                {name: "Forslag 1", link: "http://www.google.com", user: "Tester", vote: 0},
                {name: "Forslag 2", link: "http://www.google.com", user: "Tester2", vote: 0},
                {name: "Forslag 3", link: "http://www.google.com", user: "Tester", vote: 0},
            ],
        
        },

        pastVotes: [
            {name: "Tittel", date: 0},
            {name: "Tittel 2", date: 1},
        ],
        itemScores: false,
        loginError: false,
    },

    computed:{
        votingOpen() {
            return (Date.now() - this.parseDateString(this.currentVote.votingCloses)) < 0;
        }
    },
   
    methods: {
        getTimeLeft: function(name, closeDate) {
            var closeTime = this.parseDateString(closeDate);
            var timeLeft = Math.round((closeTime - Date.now())/(60*1000));
            if(timeLeft < 0) return (name + " avsluttet");
            var min = timeLeft % 60;
            var hours = Math.round(timeLeft / 60) % 24;
            var days = Math.round(timeLeft / (60*24));
            return ("Time: " + days + " days " + hours + " hours " + min + " min");
        },

        parseDateString: function(closeDate){
            var year = closeDate.date.substring(0, 4);
            var month = closeDate.date.substring(5, 7);
            var day = closeDate.date.substring(8, 10);
            var hours = closeDate.time.substring(0, 2);
            var minutes = closeDate.time.substring(3, 5);
            return (new Date(year, (month-1), day, hours, minutes, 0, 0)).getTime();
        },

        async getVoteData(){
            let data = (await axios.post(this.uri + "/getVoteData", {"date": this.currentVote.date})).data;
            this.currentVote = data;
            this.newVote.title = data.title;
            this.newVote.suggestionsCloses = data.suggestionsCloses;
            this.newVote.votingCloses = data.votingCloses;
            this.newVote.limit = data.limit;
            if(this.loggedIn) this.getUserVotes();

        },

        async getItems(){
            this.currentVote.items = (await axios.post(this.uri + "/getItems", {"date": this.currentVote.date})).data;
            if(this.loggedIn) await this.getUserVotes();
        },

        async addItem(){
            if(this.newItem.link.length < 5) this.newItem.link = 'https://www.google.com/search?q=' + this.newItem.name;
            await axios.post(this.uri + "/addItem", {'date': this.currentVote.date, 'item': this.newItem});
            this.newItem.name = "";
            this.newItem.link = "";
            this.getItems();
        },

        async createNewVote(){
            var res = await axios.post(this.uri + "/newVote", this.newVote);
            if(res.status === 200){
                this.itemScores = false;
                this.currentVote = res.data.voteData;
                this.get
            }
        },
      
        async updateVote(){
            var res = await axios.post(this.uri + "/updateVote", {'date': this.currentVote.date, 'data': this.newVote});
             if(res.status === 200){
                this.currentVote.title = this.newVote.title;
                this.currentVote.suggestionsCloses = this.newVote.suggestionsCloses;
                this.currentVote.votingCloses = this.newVote.votingCloses;
                this.currentVote.limit = this.newVote.limit;
            }
        },
        
        async getUserVotes(){
            this.setUserVotes((await axios.post(this.uri + "/getVotes", {"date": this.currentVote.date, "user": this.user.name})).data);
        },

        async vote(name, i, vote){
            if(!this.loggedIn) return;
            var res = await axios.post(this.uri + "/vote", {"date": this.currentVote.date, "user": this.user.name, "item": name, "vote": vote});
            if(res.status===200 && res.data.status !== false) this.$set(this.currentVote.items[i], 'vote', vote);
        },

        async getScores(){
            this.itemScores = (await axios.post(this.uri + "/getScores", {"date": this.currentVote.date})).data;
        },

        async login(){
            if(this.user.name.length < 1) return;
            var res = await axios.post(this.uri + "/login", {username: this.user.name, "password": this.user.password});
            if(res.status === 403) {this.loginError.status = true; this.loginError.msg = "Error: wrong password!"; return;}
            if(res.status !== 200) return;
            this.loggedIn = true;
            this.newItem.user = this.user.name;
            this.curTab = "vote";
            this.getItems();
        },

        async deleteItem(name){
            var res = await axios.post(this.uri + "/deleteItem", {"date": this.currentVote.date, "name": name});
            if(res.status) await this.getItems();
        },

        async setTheme(){await axios.post(this.uri + "/setTheme", {"themeName": this.themeName});},

        async getPastVote(e){
            this.currentVote.date = parseInt(e.target.value, 10);
            await this.getVoteData();
            this.itemScores = false;
        },

        async getPastVotes(){this.pastVotes = (await axios.post(this.uri + "/getPastVotesList")).data;},
        
        async limitToggle(e){
            this.newVote.limit = (e.target.checked ? 1 : -1) * Math.abs(this.newVote.limit);
            if(this.newVote.limit == 0) this.newVote.limit = (e.target.checked ? 1 : -1);
        },

        setUserVotes(votes){
            for(var i=0; i<this.currentVote.items.length; i++){
                this.$set(this.currentVote.items[i], 'vote', votes[this.currentVote.items[i].name] ? votes[this.currentVote.items[i].name] : 0)
            }
        },

        logout(){
            this.loggedIn = false;
            this.curTab = "login";
            this.user.name = "";
            this.user.password = "";
            this.user.votes = [];
            this.newItem = {name: "", link: "", user: ""};
            this.currentVote.items.forEach(item => {item.vote = 0;});
        },

        async mountApp(){
            this.currentVote.date = (await axios.post(this.uri + "/getCurrentVote")).data;
            await this.getVoteData();
            await this.getPastVotes();
            //For debug
            this.user = {name: "Tester", password: "password", votes: [],};
            await this.login();
        },
    },

    mounted() {
        this.mountApp();
    }
})






