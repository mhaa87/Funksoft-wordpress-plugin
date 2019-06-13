<!DOCTYPE html> 
<html> 
<head> 
</head> 
    
<body> 
		<div id="app" class="votingPugin">

        <div v-if="loggedIn" class="themeName">{{currentVote.title}}</div>

        <div class="mainContent">          
            <div v-if="loggedIn" class="tabMenu">
                <button @click="curTab = 'vote'" :class="{selected: curTab === 'vote'}">Vote</button>
                <button @click="curTab = 'suggestions'" :class="{selected: curTab === 'suggestions'}">Suggestions</button>
                <button @click="curTab = 'newVote'" :class="{selected: curTab === 'newVote'}">New/edit vote</button>
                <button @click="logout">Logout</button>
            </div>

            <div class="middleTab scroll">
                
                <form v-if="curTab === 'login'" class="login" @submit.prevent="login">
                    <span></span>
                    Username: <input type="text" name="username" placeholder="Username" v-model="user.name"><span></span><span></span>
                    Password: <input type="password" name="password" placeholder="Password" v-model="user.password"><span></span><span></span>
                    <span></span><button type="submit">Login</button><span></span>
                    <span></span><span v-if="loginError" class="loginError">{{loginError}}</span><span></span>
                </form>
                
                <div v-if="curTab === 'vote'">
                    <div v-if="votingOpen" class="voteButtons">  
                        <span class="timeLeftText">{{getTimeLeft('Stemming', currentVote.votingCloses)}}</span>
                        <template v-for="(item, i) in currentVote.items">
                            <a :href="item.link" target="_blank"><img src="https://useiconic.com/open-iconic/svg/arrow-circle-right.svg"></a>
                            <span class="gameTitle">{{item.name}}</span>
                            <button @click="vote(item.name, i, item.vote === 1 ? 0 : 1)" :class="{selected: item.vote === 1}">+</button>
                        </template>
                    </div>
                    <div v-else class="resultTab">                
                        <button @click="getScores">Calculate results</button>
                        <table v-if="itemScores !== false">
                            <tr v-for="item in itemScores">
                                <td>{{item.name}}:</td><td>{{item.votes}}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div v-if="curTab === 'suggestions'" class="suggestionsTab">
                    <span class="timeLeftText">{{getTimeLeft('Forslag', currentVote.suggestionsCloses)}}</span>
                    <span style="text-align: center">Name:</span>
                    <input v-model="newItem.name">
                    <button @click="addItem" style="grid-area: 2 / 3 / 4 / 4">Add</button>
                    <span style="text-align: center">Link:</span>
                    <input v-model="newItem.link">
                    <template v-for="item in currentVote.items.filter((e) => e.user === user.name)" class="scroll">
                        <button @click="deleteItem(item.name)">Delete</button>
                        <span class="suggestionTitle">{{item.name}}</span>            
                    </template>
                </div>

                <div v-if="curTab === 'newVote'" class="newVoteTab">
                    <span>Choose vote:</span> 
                    <select @change="getPastVote">
                        <option v-for="vote in pastVotes" :selected="currentVote.date === vote.date"
                        v-bind:value="vote.date">{{vote.title}}</option>
                    </select>
                    <span></span>         
                    Title: <input v-model="newVote.title"><span></span>
                    Suggestions closes: 
                    <input type="date" v-model="newVote.suggestionsCloses.date">
                    <input type="time" v-model="newVote.suggestionsCloses.time">
                    Voting closes: 
                    <input type="date" v-model="newVote.votingCloses.date">
                    <input type="time" v-model="newVote.votingCloses.time">
                    Max suggestions: 
                    <span>
                        <input type="checkbox" :checked="newVote.limit > 0" @change="limitToggle">
                        <input type="number" v-if="newVote.limit > 0" v-model="newVote.limit" style="width: 3em">
                    </span><span></span>
                    <button @click="updateVote">Save changes</button>
                    <button @click="createNewVote">New vote</button>
                </div>
            </div>
        </div>
    </div>
</body> 
</html>