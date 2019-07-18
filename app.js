const app = new Vue({
    el: '#app',
    data: {
        uri: "",
        curTab: "mountingApp", //'mountingApp', 'login', 'vote', 'suggestions', 'newVote'
        voteTab: "edit", //'edit', 'open', 'delete'
        user: {name: "", password: "", votes: [], },
        token: false,
        loggedIn: false,
        loggingIn: false,
        newVote: { 
            title: "", limit: 3, ratingRange: 3, timeOffset: 0,
            suggestionsCloses: {date: "2019-05-31", time: "16:00"},
            votingCloses: {date: "2019-05-31", time: "23:00"},
            voteDuringSuggestions: true,
        },
        newItem: {name: "", link: ""},
        currentVote: {
            title: "", limit: 3, ratingRange: 3, date: false,
            suggestionsCloses: 1562443200,
            votingCloses: 1562803200,
            voteDuringSuggestions: true,
        },

        //Item: {name: "Forslag 1", link: "http://www.google.com", user: "Tester", vote: 0},
        items: [],
        pastVotes: [],
        itemScores: false,
        loginError: false,
        saveVoteIcon: false,
        voteSpin: [],
        deleteLoading: [],
    },

    computed:{
        votingOpen() {
            if((Date.now() - this.currentVote.votingCloses*1000) > 0) return false;
            if(this.currentVote.voteDuringSuggestions) return true;
            return (Date.now() - this.currentVote.voteDuringSuggestions*1000) < 0;
        },
        suggestionsOpen() {return ((Date.now() - this.currentVote.suggestionsCloses*1000) < 0);},
        ratingRange(){return parseInt(this.currentVote.ratingRange);},
    },
   
    methods: {
        getTimeLeft: function(closeDate) {
            // var curDate = new Date(Date.now());
            // console.log("curDate: " + this.getDateStrings(curDate).date + " kl." + this.getDateStrings(curDate).time);
            var closeTime = new Date(closeDate*1000);
            // console.log("closeDate: " + this.getDateStrings(closeTime).date + " kl." + this.getDateStrings(closeTime).time);
            var timeLeft = Math.round((closeTime - Date.now())/(60*1000));
            if(timeLeft < 0) return (0);
            var min = timeLeft % 60;
            timeLeft = Math.round((timeLeft - min)/60);
            var hours = timeLeft % 24;
            timeLeft = Math.round((timeLeft - hours)/24);
            return (timeLeft + " days " + hours + " hours " + min + " min");
        },

        getDateStrings: function(date){
            return {date: date.getFullYear() + '-' + this.addZero(date.getMonth() + 1) + '-' + this.addZero(date.getDate()),
                time: this.addZero(date.getHours()) + ':' + this.addZero(date.getMinutes()),};
        },

        addZero(time){return time < 10 ? '0' + time : time},

        async getVoteData(){
            // console.log("getting vote data: " + this.currentVote.date);
            var res = (await axios.post(this.uri + "/getVoteData", {"date": this.currentVote.date})).data;
            if(res.status === false) return;
            // console.log(res);
            this.currentVote = res.data;
            this.currentVote.voteDuringSuggestions = parseInt(res.data.voteDuringSuggestions) === 1;
            this.newVote.title = res.data.title;
            this.newVote.suggestionsCloses = this.getDateStrings(new Date(res.data.suggestionsCloses*1000));     
            this.newVote.votingCloses = this.getDateStrings(new Date(res.data.votingCloses*1000));
            this.newVote.limit = res.data.limit;
            this.newVote.ratingRange = res.data.ratingRange;
            this.newVote.voteDuringSuggestions = res.data.voteDuringSuggestions;
            // console.log("voting data loaded");
            await this.getItems();
            await this.getUserVotes();

        },

        async getItems(){
            // console.log("getting items");
            this.items = (await axios.post(this.uri + "/getItems", {"date": this.currentVote.date})).data.data;
            this.voteSpin = new Array(this.items.length).fill(false);
            this.deleteLoading = new Array(this.items.length).fill(false);
            // console.log(this.items);
            if(this.loggedIn) await this.getUserVotes();
        },

        async addItem(){
            // console.log("adding new vote item");
            if(this.newItem.name.length < 2) return;
            this.saveVoteIcon = true;
            if(this.newItem.link.length < 5) this.newItem.link = 'https://www.google.com/search?q=' + this.newItem.name;
            var res = await axios.post(this.uri + "/addItem", {'date': this.currentVote.date, 'token': this.token,'item': this.newItem});
            // console.log("userID: " + res.data.data);
            this.newItem.name = "";
            this.newItem.link = "";
            await this.getItems();
            this.saveVoteIcon = false;
        },

        async createNewVote(){
            // console.log("creating a new vote");
            this.currentVote.date = false;
            this.currentVote.title = "";
            this.newVote.title = "";
        },
      
        async updateVote(){
            // console.log("updating vote " + this.currentVote.date);
            this.saveVoteIcon = true;
            this.newVote.timeOffset = (new Date()).getTimezoneOffset()*60;
            var res;
            if(this.currentVote.date === false){
                // console.log("creating new vote");
                if(this.newVote.title.length < 3) return;
                res = await axios.post(this.uri + "/newVote", this.newVote);
                if(res.status === 200){this.currentVote = res.data.data; console.log("new vote created");} 
            }else{ 
                res = await axios.post(this.uri + "/updateVote", {'date': this.currentVote.date, 'data': this.newVote});
                // console.log("vote was updated");
            }        

            if(res.status === 200) {await this.getVoteData(); await this.getPastVotes();}
            this.saveVoteIcon = false;
            // console.log(res.data);
        },
        
        async getUserVotes(){
            // console.log("getting votes for " + this.user.name + ", vote: " + this.currentVote.date);
            this.setUserVotes((await axios.post(this.uri + "/getVotes", {"date": this.currentVote.date, "token": this.token})).data.data);
        },

        async vote(name, r, i, vote){
            // console.log("sending vote, vote:" + vote + ", itemName: " + name + " voteID: "+ this.currentVote.date);
            if(!this.loggedIn) return;
            this.$set(this.voteSpin, r , true);
            var res = await axios.post(this.uri + "/vote", {"date": this.currentVote.date, "token": this.token, "item": name, "vote": vote});
            if(res.status===200 && res.data.status !== false) this.$set(this.items[r], 'vote', vote);
            this.$set(this.voteSpin, r , false);
        },

        async getScores(){
            // console.log("calculating results...");
            this.itemScores = (await axios.post(this.uri + "/getScores", {"date": this.currentVote.date})).data.data;
            // console.log(this.itemScores);
        },

        async authUser(){
            if(this.user.name.length < 1) {this.loginError = "Enter a username to log in"; return;}
            if(this.user.password.length < 1) {this.loginError = "Enter a password to log in"; return;}
            this.loggingIn = true;
            var res = await axios.post(this.uri + "/login", {username: this.user.name, "password": this.user.password});
            if(res.data.status === false) {this.loggingIn = false; this.loginError = res.data.data; return;}
            // console.log(res);
            await this.login(res.data.data);
        },

        async login(token){
            // console.log("logging in with " + token);
            this.token = token;
            sessionStorage.setItem("token", this.token);
            this.loginError = false;       
            // console.log("getting last vote"); 
            lastVote = (await axios.post(this.uri + "/getCurrentVote")).data.data;
            if(lastVote) {
                this.currentVote.date = lastVote;
                // console.log("getting vote data for " + this.currentVote.date); 
                await this.getVoteData();
                // console.log("getting past votes"); 
                await this.getPastVotes();
            };
            this.curTab = "vote";
            this.loggedIn = true;
            this.loggingIn = false;
        },

        async deleteItem(name, i){
            // console.log("deleting " + name + "[" + i + "] from " + this.currentVote.date);
            this.$set(this.deleteLoading, i , true);
            var res = await axios.post(this.uri + "/deleteItem", {"date": this.currentVote.date, "name": name, "token": this.token});
            // console.log(res);
            if(res.status) await this.getItems();
            this.$set(this.deleteLoading, i , false);
        },

        async setTheme(){await axios.post(this.uri + "/setTheme", {"themeName": this.themeName});},

        async getPastVote(date){
            // console.log("getting past votes...");
            this.saveVoteIcon = true;
            this.currentVote.date = parseInt(date);
            await this.getVoteData();
            await this.getItems();
            this.itemScores = false;
            this.saveVoteIcon = false;
        },

        async deleteVote(){
            this.saveVoteIcon = true;
            this.voteTab = "edit";
            await axios.post(this.uri + "/deleteVote", {"date": this.currentVote.date});
            lastVote = (await axios.post(this.uri + "/getCurrentVote")).data.data;
            this.itemScores = false;
            if(lastVote === null){return};
            this.currentVote.date = lastVote;
            await this.getVoteData();
            await this.getItems();
            await this.getPastVotes();
            this.saveVoteIcon = false;
        },

        async getPastVotes(){
            // console.log("getting past votes");
            this.pastVotes = (await axios.post(this.uri + "/getPastVotesList")).data.data;
            // console.log(this.pastVotes);
        },
        
        async limitToggle(e){
            this.newVote.limit = (e.target.checked ? 1 : -1) * Math.abs(this.newVote.limit);
            if(this.newVote.limit == 0) this.newVote.limit = (e.target.checked ? 1 : -1);
        },

        setUserVotes(votes){
            // console.log("Setting user votes: ");
            // console.log(votes);
            for(var i=0; i<this.items.length; i++){
                this.$set(this.items[i], 'vote', votes[this.items[i].name] ? parseInt(votes[this.items[i].name].vote) : 0)
            }
        },

        logout(){
            this.loggedIn = false;
            this.curTab = "login";
            this.user.name = "";
            this.user.password = "";
            this.user.votes = [];
            this.newItem = {name: "", link: ""};
            this.token = false;
            this.items = [];
        },

        async autoLogin(token){
            // console.log("autologging with " + token);
            var res = (await axios.post(this.uri + "/autoLogin", {"token": token})).data;
            if(res.status === false) {this.loggingIn = false; return;}
            this.user.name = res.data.name;
            await this.login(res.data.token);
        },

        async mountApp(){
            this.curTab = "mountingApp";
            this.uri = pluginInfo.url + "funksoftvote/v1";
            this.newVote.timeOffset = (new Date()).getTimezoneOffset()*60;
            var oldToken = sessionStorage.getItem("token");
            if(oldToken && oldToken.length > 5){await this.autoLogin(oldToken)};
            if(this.curTab === 'mountingApp' ) this.curTab = "login";
            // console.log("timezone: " + (new Date()).getTimezoneOffset());
        },
    },

    mounted() {
        this.mountApp();
    }
})






